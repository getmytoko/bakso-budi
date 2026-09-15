// ===============================
// KONFIGURASI DATA PRODUK
// ===============================
// Ganti gambar dengan path file lokal Anda, contoh: "images/bolu-talas.jpg"
// Jika gambar tidak ditemukan, akan otomatis memakai placeholder.

const produk = [
  {
    nama: "Bolu Gulung Signature Talas Rasa Keju",
    kategori: "Signature",
    harga: "Rp46.000",
    gambar: "images/bolu-gulung-signature-talas.jpg"
  },
  {
    nama: "Donat Kukus Cokelat",
    kategori: "Donat Kukus",
    harga: "Rp42.000",
    gambar: "images/donat-kukus-cokelat.jpg"
  },
  {
    nama: "Donat Kukus Talas Cokelat",
    kategori: "Donat Kukus",
    harga: "Rp25.000",
    gambar: "images/donat-kukus-talas-cokelat.jpg"
  },
  {
    nama: "Dessert Cup Cookies and Cream",
    kategori: "Dessert Cup",
    harga: "Rp15.000",
    gambar: "images/dessert-cup-cookies-cream.jpg"
  }
];

// Daftar kategori & ikon untuk filter
// Ikon memakai emoji, bisa diganti dengan <img> jika diperlukan.
const kategori = [
  { nama: "Signature", icon: "🍰" },
  { nama: "Lapis Reguler", icon: "🥮" },
  { nama: "Donat Kukus", icon: "🍩" },
  { nama: "Lapis Mini", icon: "🧁" },
  { nama: "Dessert Cup", icon: "🍮" }
];