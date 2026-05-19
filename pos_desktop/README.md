<p align="center">
  <img src="public/logo.png" alt="Socibiz" width="220" />
</p>

<h1 align="center">Socibiz POS Desktop</h1>

<p align="center">
  Qt 6 desktop point-of-sale client for <strong>Socibiz</strong>.<br>
  Uses the same REST API as the web <strong>Online POS</strong> (<code>Modules/Pos</code>).
</p>

<p align="center">
  <a href="https://github.com/Zeebroo-Team/pos-desktop">Repository</a>
  ·
  <a href="#build">Build</a>
  ·
  <a href="#deploy">Deploy</a>
  ·
  <a href="#git-submodule-socibiz">Submodule</a>
</p>

---

## Features

- Sign in with Sanctum API token (`POST /api/v1/pos/auth/token`)
- Business picker (`GET /api/v1/pos/businesses`)
- Three-panel UI aligned with the web terminal: **Current sale**, **Product catalog**, **Checkout**
- Category filters, name search, SKU scan
- Multi-batch stock layer picker when required
- Cash / card / credit checkout with numpad (`POST /api/v1/pos/online/checkout`)
- Receipt dialog after sale with **thermal printer** support (80mm paper preset)

## Requirements

| Component | Version |
|-----------|---------|
| **Qt** | 6.x (Core, Gui, Widgets, Network, Multimedia, PrintSupport) |
| **CMake** | 3.21+ |
| **Compiler** | C++17 |
| **Backend** | Running **Socibiz** Laravel app with POS API (`laravel/sanctum`, POS module routes) |

---

## Git submodule (Socibiz)

This repository is designed to live inside the main Socibiz monorepo at **`pos_desktop/`** as a git submodule.

### Clone Socibiz with the desktop client

```bash
git clone --recurse-submodules https://github.com/Zeebroo-Team/socibiz.git
cd socibiz
```

If you already cloned Socibiz without submodules:

```bash
cd socibiz
git submodule update --init --recursive
```

### Add this repo as a submodule (new Socibiz checkout)

From the root of your Socibiz clone:

```bash
git submodule add https://github.com/Zeebroo-Team/pos-desktop.git pos_desktop
git submodule update --init --recursive
git add .gitmodules pos_desktop
git commit -m "Add pos_desktop submodule."
```

Use SSH instead of HTTPS if your environment has no GitHub credential helper:

```bash
git submodule add git@github.com:Zeebroo-Team/pos-desktop.git pos_desktop
```

### Update to the latest desktop client

```bash
cd pos_desktop
git fetch origin
git checkout main
git pull origin main
cd ..
git add pos_desktop
git commit -m "Bump pos_desktop submodule."
```

### Pin a specific release

```bash
cd pos_desktop
git checkout v1.0.0   # tag or commit SHA
cd ..
git add pos_desktop
git commit -m "Pin pos_desktop to v1.0.0."
```

---

## Deploy

### 1. Deploy the Socibiz backend

The desktop app does **not** bundle the API. Deploy Socibiz first so these are available:

- HTTPS base URL for your app (e.g. `https://pos.example.com`)
- POS API routes under `/api/v1/pos`
- Sanctum personal access tokens for terminal sign-in
- POS module enabled and configured (accounts, products, stock layers)

API reference (in Socibiz): `Modules/Pos/docs/API.md`  
Interactive docs: `GET https://your-app.test/api/v1/pos/docs`

### 2. Build the desktop binary

On the machine that will run the terminal (or your build server):

```bash
cd pos_desktop   # or socibiz/pos_desktop when used as a submodule

cmake -B build \
  -DCMAKE_BUILD_TYPE=Release \
  -DCMAKE_PREFIX_PATH="$(qtpaths6 --install-prefix 2>/dev/null || echo /opt/homebrew/opt/qt)"

cmake --build build --config Release
```

**macOS (Homebrew):**

```bash
brew install qt cmake
```

**Output:** `build/SocibizPosDesktop` (macOS/Linux) or `build/Release/SocibizPosDesktop.exe` (Windows, depending on generator).

Optional install to a system path:

```bash
cmake --install build --prefix /opt/socibiz-pos
```

### 3. Configure the terminal

Copy `config.example.json` and set your production API URL:

```json
{
  "api_base_url": "https://your-app.example.com/api/v1/pos",
  "device_name": "pos-desktop-1"
}
```

| Platform | Config file location |
|----------|----------------------|
| **macOS** | `~/Library/Application Support/Socibiz/PosDesktop/config.json` |
| **Linux** | `~/.config/Socibiz/PosDesktop/config.json` (typical) |
| **Windows** | `%APPDATA%/Socibiz/PosDesktop/config.json` (typical) |

You can also place `config.json` next to the executable, or set the API URL on the sign-in screen.

Use a unique `device_name` per physical terminal (shown in Sanctum token list).

### 4. Run in production

```bash
./build/SocibizPosDesktop
```

**Checklist**

- [ ] Backend URL uses **HTTPS** in production
- [ ] Firewall allows outbound HTTPS from the terminal to the Socibiz host
- [ ] Staff user has access to the target business (`X-Business-Id` is set automatically after login)
- [ ] Cash/card checkout accounts exist in POS settings
- [ ] Thermal printer installed and selected in the OS print dialog when printing receipts

### 5. Ship updates

1. Build and test a new binary from an updated `pos_desktop` commit or tag.
2. Distribute the executable (or installer) to each store machine.
3. If Socibiz uses the submodule, bump `pos_desktop` in the parent repo so developers stay in sync.

---

## Configuration reference

```json
{
  "api_base_url": "https://your-app.test/api/v1/pos",
  "device_name": "pos-desktop-1"
}
```

| Key | Description |
|-----|-------------|
| `api_base_url` | Full POS API base (no trailing slash required) |
| `device_name` | Sanctum token device label for this terminal |

---

## Build (development)

```bash
cd pos_desktop
cmake -B build -DCMAKE_PREFIX_PATH="$(qtpaths6 --install-prefix 2>/dev/null || echo /opt/homebrew/opt/qt)"
cmake --build build
./build/SocibizPosDesktop
```

---

## API reference

| Resource | Location |
|----------|----------|
| OpenAPI / Swagger UI | `GET /api/v1/pos/docs` on your Socibiz host |
| Markdown guide | `Modules/Pos/docs/API.md` in the Socibiz repo |

Authenticated requests send `Authorization: Bearer {token}` and `X-Business-Id` after login.

---

## Project layout

```
pos_desktop/
  CMakeLists.txt
  config.example.json
  public/
    logo.png
  resources/
    styles.qss
    sounds/beep.wav
    resources.qrc
  src/
    main.cpp
    core/          ApiClient, Cart, Models, Config, SaleReceipt
    ui/            LoginDialog, MainWindow, LayerPickerDialog, ReceiptDialog
```

---

## Notes

- Send `X-Business-Id` on every authenticated request (handled automatically after login).
- Cash and card payments require a deposit account (from POS settings / bootstrap `accounts`).
- When `requires_layer_pick` is true on a product, the app prompts for a stock batch before adding to cart.

---

## Related repositories

| Repo | Role |
|------|------|
| [Zeebroo-Team/socibiz](https://github.com/Zeebroo-Team/socibiz) | Laravel application; hosts POS API |
| [Zeebroo-Team/pos-desktop](https://github.com/Zeebroo-Team/pos-desktop) | This Qt desktop client (submodule at `pos_desktop/`) |
