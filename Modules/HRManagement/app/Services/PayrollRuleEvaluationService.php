<?php

declare(strict_types=1);

namespace Modules\HRManagement\Services;

use Modules\HRManagement\Models\PayrollRule;

final class PayrollRuleEvaluationService
{
    /**
     * @param  array<string, float|int|string|null>  $context
     * @return array{amount: float, quantity: float, rate: float, meta: array<string, mixed>, errors: list<string>}
     */
    public function evaluate(PayrollRule $rule, array $context): array
    {
        $cfg = is_array($rule->config_json) ? $rule->config_json : [];
        $errors = [];
        $quantity = 1.0;
        $rate = 0.0;
        $amount = 0.0;
        $meta = [];

        if (! $rule->is_active) {
            return [
                'amount' => 0.0,
                'quantity' => $quantity,
                'rate' => $rate,
                'meta' => ['skipped' => true],
                'errors' => [],
            ];
        }

        $mode = (string) $rule->calculation_mode;
        if ($mode === PayrollRule::MODE_FIXED) {
            $amount = round((float) ($cfg['amount'] ?? 0), 2);
            $rate = $amount;
        } elseif ($mode === PayrollRule::MODE_PERCENTAGE) {
            $percent = (float) ($cfg['percent'] ?? 0);
            $baseField = (string) ($cfg['base_field'] ?? 'basic_salary');
            $baseValue = (float) ($context[$baseField] ?? 0);
            $amount = round(($baseValue * $percent) / 100, 2);
            $rate = $percent;
            $meta['base_field'] = $baseField;
            $meta['base_value'] = round($baseValue, 2);
        } elseif ($mode === PayrollRule::MODE_SLAB) {
            $inputField = (string) ($cfg['input_field'] ?? 'taxable_earnings');
            $slabs = isset($cfg['slabs']) && is_array($cfg['slabs']) ? $cfg['slabs'] : [];
            $input = (float) ($context[$inputField] ?? 0);
            [$amount, $slabMeta] = $this->evaluateSlabs($input, $slabs);
            $rate = 0;
            $meta = [
                'input_field' => $inputField,
                'input_value' => round($input, 2),
                'slab_breakdown' => $slabMeta,
            ];
        } elseif ($mode === PayrollRule::MODE_FORMULA) {
            $formula = trim((string) ($cfg['formula'] ?? ''));
            if ($formula === '') {
                $errors[] = 'Missing formula expression.';
            } else {
                $formulaResult = $this->evaluateFormula($formula, $context);
                $amount = round($formulaResult['value'], 2);
                $errors = array_merge($errors, $formulaResult['errors']);
                $meta['formula'] = $formula;
            }
        } else {
            $errors[] = 'Unsupported calculation mode: '.$mode;
        }

        if ($amount < 0) {
            $amount = round($amount, 2);
        }

        return [
            'amount' => $amount,
            'quantity' => $quantity,
            'rate' => $rate,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, mixed>  $slabs
     * @return array{0: float, 1: array<int, array<string, float>>}
     */
    private function evaluateSlabs(float $income, array $slabs): array
    {
        $remaining = max(0, $income);
        $total = 0.0;
        $breakdown = [];

        usort($slabs, static function ($a, $b): int {
            $aFrom = (float) (is_array($a) ? ($a['from'] ?? 0) : 0);
            $bFrom = (float) (is_array($b) ? ($b['from'] ?? 0) : 0);

            return $aFrom <=> $bFrom;
        });

        foreach ($slabs as $slab) {
            if (! is_array($slab)) {
                continue;
            }
            $from = max(0, (float) ($slab['from'] ?? 0));
            $to = isset($slab['to']) && $slab['to'] !== null ? (float) $slab['to'] : null;
            $percent = max(0, (float) ($slab['percent'] ?? 0));
            $fixed = max(0, (float) ($slab['fixed'] ?? 0));

            if ($income <= $from || $remaining <= 0) {
                continue;
            }

            $sliceUpper = $to === null ? $income : min($income, $to);
            $sliceBase = max(0.0, $sliceUpper - $from);
            $sliceAmount = round(($sliceBase * $percent) / 100 + $fixed, 2);
            if ($sliceAmount <= 0 && $sliceBase <= 0) {
                continue;
            }

            $total += $sliceAmount;
            $remaining = max(0, $remaining - $sliceBase);
            $breakdown[] = [
                'from' => $from,
                'to' => $to ?? -1,
                'taxable' => round($sliceBase, 2),
                'percent' => $percent,
                'fixed' => $fixed,
                'amount' => $sliceAmount,
            ];
        }

        return [round($total, 2), $breakdown];
    }

    /**
     * @param  array<string, float|int|string|null>  $context
     * @return array{value: float, errors: list<string>}
     */
    private function evaluateFormula(string $formula, array $context): array
    {
        $errors = [];
        $allowed = [];
        foreach ($context as $key => $val) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $key) !== 1) {
                continue;
            }
            $allowed[(string) $key] = (float) $val;
        }

        $expr = preg_replace_callback(
            '/[A-Za-z_][A-Za-z0-9_]*/',
            static function (array $m) use ($allowed): string {
                $key = $m[0];
                if (array_key_exists($key, $allowed)) {
                    return (string) $allowed[$key];
                }

                return '0';
            },
            $formula
        );

        if ($expr === null || preg_match('/[^0-9+\-*\/().\s]/', $expr) === 1) {
            return ['value' => 0.0, 'errors' => ['Formula contains unsupported tokens.']];
        }

        try {
            $value = $this->evaluateArithmeticExpression($expr);
        } catch (\Throwable $e) {
            $errors[] = 'Formula evaluation failed.';
            $value = 0.0;
        }

        return ['value' => (float) $value, 'errors' => $errors];
    }

    private function evaluateArithmeticExpression(string $expr): float
    {
        $tokens = $this->tokenize($expr);
        $output = [];
        $ops = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = (float) $token;

                continue;
            }
            if ($token === '(') {
                $ops[] = $token;

                continue;
            }
            if ($token === ')') {
                while (! empty($ops) && end($ops) !== '(') {
                    $this->reduceStack($output, (string) array_pop($ops));
                }
                array_pop($ops);

                continue;
            }
            while (! empty($ops) && end($ops) !== '(' && $precedence[(string) end($ops)] >= $precedence[$token]) {
                $this->reduceStack($output, (string) array_pop($ops));
            }
            $ops[] = $token;
        }

        while (! empty($ops)) {
            $this->reduceStack($output, (string) array_pop($ops));
        }

        return (float) ($output[0] ?? 0.0);
    }

    /** @return list<string> */
    private function tokenize(string $expr): array
    {
        $expr = preg_replace('/\s+/', '', $expr) ?? '';
        preg_match_all('/\d+(?:\.\d+)?|[()+\-*\/]/', $expr, $m);

        return $m[0] ?? [];
    }

    /** @param  array<int, float>  $stack */
    private function reduceStack(array &$stack, string $op): void
    {
        $b = array_pop($stack) ?? 0.0;
        $a = array_pop($stack) ?? 0.0;
        $res = match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
            '/' => abs($b) < 0.0000001 ? 0.0 : $a / $b,
            default => 0.0,
        };
        $stack[] = $res;
    }
}
