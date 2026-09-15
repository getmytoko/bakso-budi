<?php
declare(strict_types=1);

$storePath = __DIR__ . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'store.json';
$productsPath = __DIR__ . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'products.json';

$defaultStore = [
    'name' => '',
    'whatsapp' => '',
    'description' => '',
    'location' => '',
];

$categories = ['Signature', 'Lapis Reguler', 'Donat Kukus', 'Lapis Mini', 'Dessert Cup'];
$feedback = null;
$errors = [];

function readJsonFile(string $path, array $fallback): array
{
    if (!is_file($path)) {
        return $fallback;
    }

    $contents = file_get_contents($path);
    if ($contents === false || trim($contents) === '') {
        return $fallback;
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function saveJsonFile(string $path, array $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    $temporaryPath = $path . '.tmp';
    if (file_put_contents($temporaryPath, $json . PHP_EOL, LOCK_EX) === false) {
        return false;
    }

    if (!rename($temporaryPath, $path)) {
        @unlink($temporaryPath);
        return false;
    }

    return true;
}

function cleanValue(?string $value): string
{
    return trim((string) $value);
}

$store = array_merge($defaultStore, readJsonFile($storePath, []));
$productData = readJsonFile($productsPath, ['items' => []]);
$products = isset($productData['items']) && is_array($productData['items'])
    ? array_values($productData['items'])
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanValue($_POST['action'] ?? '');

    if ($action === 'save_store') {
        $store = [
            'name' => cleanValue($_POST['name'] ?? ''),
            'whatsapp' => cleanValue($_POST['whatsapp'] ?? ''),
            'description' => cleanValue($_POST['description'] ?? ''),
            'location' => cleanValue($_POST['location'] ?? ''),
        ];

        if ($store['name'] === '') {
            $errors[] = 'Nama toko wajib diisi.';
        }

        if (!$errors && saveJsonFile($storePath, $store)) {
            $feedback = 'Pengaturan toko berhasil disimpan.';
        } elseif (!$errors) {
            $errors[] = 'Pengaturan toko gagal disimpan. Pastikan folder web/data dapat ditulis.';
        }
    } elseif ($action === 'save_product') {
        $index = filter_var($_POST['index'] ?? '', FILTER_VALIDATE_INT);
        $product = [
            'nama' => cleanValue($_POST['nama'] ?? ''),
            'kategori' => cleanValue($_POST['kategori'] ?? ''),
            'harga' => cleanValue($_POST['harga'] ?? ''),
            'gambar' => cleanValue($_POST['gambar'] ?? ''),
        ];

        if ($product['nama'] === '' || $product['kategori'] === '' || $product['harga'] === '') {
            $errors[] = 'Nama produk, kategori, dan harga wajib diisi.';
        } elseif ($product['gambar'] === '') {
            $product['gambar'] = '';
        }

        if (!$errors) {
            if ($index !== false && $index >= 0 && $index < count($products)) {
                $products[$index] = array_merge($products[$index], $product);
                $message = 'Produk berhasil diperbarui.';
            } else {
                $products[] = $product;
                $message = 'Produk baru berhasil ditambahkan.';
            }

            if (saveJsonFile($productsPath, ['items' => $products])) {
                $feedback = $message;
            } else {
                $errors[] = 'Produk gagal disimpan. Pastikan file products.json dapat ditulis.';
            }
        }
    } elseif ($action === 'delete_product') {
        $index = filter_var($_POST['index'] ?? '', FILTER_VALIDATE_INT);
        if ($index === false || $index < 0 || $index >= count($products)) {
            $errors[] = 'Produk yang akan dihapus tidak ditemukan.';
        } else {
            array_splice($products, $index, 1);
            if (saveJsonFile($productsPath, ['items' => $products])) {
                $feedback = 'Produk berhasil dihapus.';
            } else {
                $errors[] = 'Produk gagal dihapus. Pastikan file products.json dapat ditulis.';
            }
        }
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function productValue(array $product, string $key): string
{
  $value = (string) ($product[$key] ?? '');
  if ($key === 'harga') {
    $value = preg_replace('/[^0-9]/', '', $value) ?? '';
  }

  return escapeHtml($value);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup Offline | MyToko</title>
  <style>
    :root {
      --ink: #20242b;
      --muted: #6d747e;
      --line: #e2e5e9;
      --paper: #ffffff;
      --canvas: #f4f6f8;
      --accent: #6d3f2c;
      --accent-dark: #4d2a1e;
      --success: #176b45;
      --success-bg: #e6f5ec;
      --danger: #a33b35;
      --danger-bg: #fff0ef;
      --shadow: 0 18px 45px rgba(32, 36, 43, .08);
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      color: var(--ink);
      background: var(--canvas);
      font: 15px/1.5 Georgia, 'Times New Roman', serif;
    }
    button, input, textarea, select { font: inherit; }
    .shell { width: min(1180px, calc(100% - 32px)); margin: 0 auto; padding: 42px 0 64px; }
    .masthead { display: flex; justify-content: space-between; gap: 24px; align-items: end; margin-bottom: 28px; }
    .eyebrow { margin: 0 0 7px; color: var(--accent); font: 700 12px/1.2 Arial, sans-serif; letter-spacing: .12em; text-transform: uppercase; }
    h1, h2, p { margin-top: 0; }
    h1 { max-width: 680px; margin-bottom: 8px; font-size: clamp(2.1rem, 5vw, 4rem); line-height: 1; letter-spacing: -.045em; font-weight: 700; }
    h2 { margin-bottom: 6px; font-size: 1.65rem; letter-spacing: -.025em; }
    .intro { max-width: 650px; margin-bottom: 0; color: var(--muted); font-size: 1.05rem; }
    .path-note { color: var(--muted); font: 12px/1.4 Arial, sans-serif; text-align: right; }
    .notice { margin: 0 0 22px; padding: 13px 16px; border-radius: 10px; font-family: Arial, sans-serif; }
    .notice.success { color: var(--success); background: var(--success-bg); border: 1px solid #b8e3ca; }
    .notice.error { color: var(--danger); background: var(--danger-bg); border: 1px solid #f0c5c1; }
    .layout { display: grid; grid-template-columns: minmax(280px, .72fr) minmax(0, 1.28fr); gap: 22px; align-items: start; }
    .panel { padding: 26px; background: var(--paper); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow); }
    .panel-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 20px; }
    .count { color: var(--muted); font: 12px Arial, sans-serif; }
    .field { margin-bottom: 16px; }
    label { display: block; margin-bottom: 6px; color: #4e555e; font: 700 12px Arial, sans-serif; letter-spacing: .04em; text-transform: uppercase; }
    input, textarea, select { width: 100%; padding: 11px 12px; color: var(--ink); background: #fbfcfd; border: 1px solid #cfd4da; border-radius: 8px; outline: 0; }
    input:focus, textarea:focus, select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(109, 63, 44, .13); }
    textarea { min-height: 104px; resize: vertical; }
    .actions { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 20px; }
    button { padding: 10px 15px; color: #fff; background: var(--accent); border: 0; border-radius: 7px; cursor: pointer; font: 700 13px Arial, sans-serif; }
    button:hover { background: var(--accent-dark); }
    button.secondary { color: var(--accent); background: #f2ebe7; }
    button.danger { color: var(--danger); background: transparent; border: 1px solid #e6b6b1; }
    .product-list { display: grid; gap: 12px; }
    .product { padding: 17px; border: 1px solid var(--line); border-radius: 10px; background: #fcfcfb; }
    .product-grid { display: grid; grid-template-columns: 1.35fr 1fr 130px; gap: 10px; }
    .product-grid .wide { grid-column: span 2; }
    .product-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
    .empty { padding: 26px 10px; color: var(--muted); text-align: center; border: 1px dashed #cfd4da; border-radius: 9px; }
    .add-product { margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--line); }
    .add-product h3 { margin: 0 0 16px; font-size: 1.15rem; }
    @media (max-width: 800px) {
      .masthead { display: block; }
      .path-note { margin-top: 14px; text-align: left; }
      .layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
      .shell { width: min(100% - 22px, 1180px); padding-top: 26px; }
      .panel { padding: 19px; }
      .product-grid { grid-template-columns: 1fr; }
      .product-grid .wide { grid-column: auto; }
    }
  </style>
</head>
<body>
  <main class="shell">
    <header class="masthead">
      <div>
        <p class="eyebrow">Setup offline</p>
        <h1>Siapkan identitas toko dan katalog.</h1>
        <p class="intro">Gunakan halaman ini saat setup awal lokal. Data disimpan langsung ke folder <strong>web/data</strong>.</p>
      </div>
      <p class="path-note">store.json<br>products.json</p>
    </header>

    <?php if ($feedback): ?>
      <div class="notice success" role="status"><?= escapeHtml($feedback) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
      <div class="notice error" role="alert"><?= escapeHtml($error) ?></div>
    <?php endforeach; ?>

    <div class="layout">
      <section class="panel">
        <div class="panel-head">
          <div>
            <p class="eyebrow">Identitas</p>
            <h2>Pengaturan toko</h2>
          </div>
        </div>
        <form method="post">
          <input type="hidden" name="action" value="save_store">
          <div class="field">
            <label for="name">Nama Toko</label>
            <input id="name" name="name" type="text" value="<?= escapeHtml($store['name']) ?>" required>
          </div>
          <div class="field">
            <label for="whatsapp">Nomor WhatsApp</label>
            <input id="whatsapp" name="whatsapp" type="text" value="<?= escapeHtml($store['whatsapp']) ?>" placeholder="+6281234567890">
          </div>
          <div class="field">
            <label for="description">Deskripsi Toko</label>
            <textarea id="description" name="description" placeholder="Ceritakan singkat tentang toko Anda."><?= escapeHtml($store['description']) ?></textarea>
          </div>
          <div class="field">
            <label for="location">Alamat / Lokasi</label>
            <textarea id="location" name="location" placeholder="Alamat lengkap toko."><?= escapeHtml($store['location']) ?></textarea>
          </div>
          <div class="actions"><button type="submit">Simpan pengaturan</button></div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-head">
          <div>
            <p class="eyebrow">Katalog</p>
            <h2>Kelola produk</h2>
          </div>
          <span class="count"><?= count($products) ?> produk</span>
        </div>

        <?php if (!$products): ?>
          <p class="empty">Belum ada produk. Tambahkan produk pertama di bawah.</p>
        <?php else: ?>
          <div class="product-list">
            <?php foreach ($products as $index => $product): ?>
              <form class="product" method="post">
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="index" value="<?= $index ?>">
                <div class="product-grid">
                  <div class="field wide">
                    <label for="nama-<?= $index ?>">Nama Produk</label>
                    <input id="nama-<?= $index ?>" name="nama" type="text" value="<?= productValue($product, 'nama') ?>" required>
                  </div>
                  <div class="field">
                    <label for="harga-<?= $index ?>">Harga (angka)</label>
                    <input id="harga-<?= $index ?>" name="harga" type="number" min="0" step="1" value="<?= productValue($product, 'harga') ?>" required>
                  </div>
                  <div class="field">
                    <label for="kategori-<?= $index ?>">Kategori</label>
                    <select id="kategori-<?= $index ?>" name="kategori" required>
                      <?php $currentCategory = (string) ($product['kategori'] ?? ''); ?>
                      <?php if ($currentCategory !== '' && !in_array($currentCategory, $categories, true)): ?>
                        <option value="<?= escapeHtml($currentCategory) ?>" selected><?= escapeHtml($currentCategory) ?></option>
                      <?php endif; ?>
                      <?php foreach ($categories as $category): ?>
                        <option value="<?= escapeHtml($category) ?>" <?= $currentCategory === $category ? 'selected' : '' ?>><?= escapeHtml($category) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field wide">
                    <label for="gambar-<?= $index ?>">Path / URL Foto Produk</label>
                    <input id="gambar-<?= $index ?>" name="gambar" type="text" value="<?= productValue($product, 'gambar') ?>" placeholder="images/nama-produk.jpg">
                  </div>
                </div>
                <div class="product-actions">
                  <button type="submit">Simpan perubahan</button>
                  <button class="danger" type="submit" name="action" value="delete_product" formnovalidate onclick="return confirm('Hapus produk ini?');">Hapus</button>
                </div>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="add-product">
          <h3>Tambah produk baru</h3>
          <form method="post">
            <input type="hidden" name="action" value="save_product">
            <div class="product-grid">
              <div class="field wide">
                <label for="new-nama">Nama Produk</label>
                <input id="new-nama" name="nama" type="text" required>
              </div>
              <div class="field">
                <label for="new-harga">Harga (angka)</label>
                <input id="new-harga" name="harga" type="number" min="0" step="1" required>
              </div>
              <div class="field">
                <label for="new-kategori">Kategori</label>
                <select id="new-kategori" name="kategori" required>
                  <option value="">Pilih kategori</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= escapeHtml($category) ?>"><?= escapeHtml($category) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field wide">
                <label for="new-gambar">Path / URL Foto Produk</label>
                <input id="new-gambar" name="gambar" type="text" placeholder="images/nama-produk.jpg">
              </div>
            </div>
            <div class="actions"><button type="submit">Tambah produk</button></div>
          </form>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
