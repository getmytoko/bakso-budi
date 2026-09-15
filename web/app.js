const DEFAULT_WA_NUMBER = "081234567890";
let waAktif = DEFAULT_WA_NUMBER;

const KATEGORI = [
  { nama: "Semua", icon: "🛍️" },
  { nama: "Signature", icon: "🍰" },
  { nama: "Lapis Reguler", icon: "🥮" },
  { nama: "Donat Kukus", icon: "🍩" },
  { nama: "Lapis Mini", icon: "🧁" },
  { nama: "Dessert Cup", icon: "🍮" }
];

const PLACEHOLDER =
  "data:image/svg+xml," +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400">' +
      '<rect width="100%" height="100%" fill="#f4f4f5"/>' +
      '<text x="50%" y="45%" font-size="90" text-anchor="middle" dominant-baseline="middle">🍰</text>' +
      '<text x="50%" y="80%" font-size="22" fill="#18181b" text-anchor="middle" dominant-baseline="middle">Foto belum tersedia</text>' +
      "</svg>"
  );

const WHATSAPP_SVG =
  '<svg class="h-4 w-4 shrink-0 fill-current" viewBox="0 0 32 32"><path d="M16 2C8.27 2 2 8.24 2 15.94c0 2.8.79 5.42 2.16 7.65L2 30l6.58-2.11a13.94 13.94 0 0 0 7.42 2.1h.01C23.73 29.99 30 23.75 30 16.05 30 8.24 23.73 2 16 2Zm0 25.31h-.01c-2.29 0-4.53-.64-6.48-1.85l-.47-.27-4.01 1.29 1.3-3.9-.3-.48a11.3 11.3 0 0 1-1.75-6.05c0-6.28 5.12-11.38 11.42-11.38 3.05 0 5.92 1.19 8.08 3.35a11.34 11.34 0 0 1 3.35 8.08c0 6.28-5.13 11.21-11.43 11.21Zm6.26-8.5c-.34-.17-2.02-1-2.33-1.11-.31-.11-.54-.17-.76.17-.23.34-.88 1.11-1.08 1.34-.2.23-.4.25-.73.09-.34-.17-1.43-.53-2.72-1.68a10.22 10.22 0 0 1-1.88-2.34c-.2-.34-.02-.53.15-.7.15-.15.34-.4.5-.6.17-.2.23-.34.34-.57.11-.23.06-.42-.03-.6-.09-.17-.76-1.84-1.05-2.52-.27-.66-.55-.57-.76-.58h-.65c-.23 0-.6.09-.91.42-.31.34-1.2 1.17-1.2 2.85 0 1.68 1.23 3.31 1.4 3.54.17.23 2.42 3.69 5.86 5.17.82.35 1.46.56 1.96.72.82.26 1.57.22 2.16.13.66-.09 2.02-.82 2.31-1.62.28-.8.28-1.48.2-1.62-.09-.17-.34-.28-.7-.42Z"/></svg>';

const state = {
  kategoriAktif: "Semua",
  cari: "",
  urutan: "default",
  produk: [],
  toko: {}
};

const judulHeroEl = document.getElementById("judulHero");
const namaTokoEl = document.getElementById("namaToko");
const waTokoEl = document.getElementById("waToko");
const waTokoTextEl = document.getElementById("waTokoText");
const btnLokasiEl = document.getElementById("btnLokasi");
const modalEl = document.getElementById("modal-lokasi");
const modalFotoEl = document.getElementById("modalFoto");
const modalAlamatEl = document.getElementById("modalAlamat");
const modalTutupEl = document.getElementById("modalTutup");
const kategoriListEl = document.getElementById("kategoriList");
const produkGridEl = document.getElementById("produkGrid");
const inputCariEl = document.getElementById("inputCari");
const urutHargaEl = document.getElementById("urutHarga");
const scrollTopBtn = document.getElementById("scrollTopBtn");

function nomorWa(no) {
  if (!no) return "";
  let bersih = String(no).replace(/[^\d]/g, "");
  if (bersih.startsWith("0")) bersih = "62" + bersih.slice(1);
  return bersih;
}

function nominalHarga(str) {
  const angka = parseFloat(String(str || "").replace(/[^\d]/g, ""));
  return isNaN(angka) ? 0 : angka;
}

async function muatPengaturan() {
  try {
    const res = await fetch("data/settings.json");
    if (!res.ok) throw new Error("HTTP " + res.status);
    state.toko = await res.json();
  } catch (err) {
    state.toko = {};
  }

  const nama = state.toko.nama_toko || namaTokoEl.textContent;
  const wa = nomorWa(state.toko.whatsapp_toko) || nomorWa(waAktif);

  namaTokoEl.textContent = nama;
  document.title = nama;
  if (state.toko.nama_toko) {
    judulHeroEl.textContent = state.toko.nama_toko;
  }

  if (state.toko.whatsapp_toko) {
    waTokoTextEl.textContent = state.toko.whatsapp_toko;
  }
  waAktif = wa;
  waTokoEl.href =
    "https://wa.me/" +
    wa +
    "?text=" +
    encodeURIComponent("Halo, saya ingin bertanya tentang produk Anda.");

  if (state.toko.foto_toko) {
    modalFotoEl.src = state.toko.foto_toko;
  }
  modalFotoEl.addEventListener("error", function () {
    modalFotoEl.src = PLACEHOLDER;
  });
  modalAlamatEl.textContent = state.toko.alamat_toko || "";
}

async function muatProduk() {
  try {
    const res = await fetch("data/products.json");
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();
    state.produk = Array.isArray(data.items) ? data.items : [];
  } catch (err) {
    produkGridEl.innerHTML =
      '<p class="col-span-full py-16 text-center text-sm text-zinc-400">Gagal memuat data produk: ' +
      err.message +
      "</p>";
    state.produk = [];
  } finally {
    renderKategori();
    renderProduk();
  }
}

function bukaModal() {
  modalEl.classList.remove("hidden");
  modalEl.classList.add("flex");
}

function tutupModal() {
  modalEl.classList.add("hidden");
  modalEl.classList.remove("flex");
}

btnLokasiEl.addEventListener("click", bukaModal);
modalTutupEl.addEventListener("click", tutupModal);
modalEl.querySelector(".modal-overlay").addEventListener("click", tutupModal);
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") tutupModal();
});

function renderKategori() {
  kategoriListEl.innerHTML = "";
  KATEGORI.forEach(function (kat) {
    const aktif = state.kategoriAktif === kat.nama;
    const item = document.createElement("div");
    item.className =
      "shrink-0 cursor-pointer select-none rounded-full px-4 py-2 text-center text-sm font-medium transition-all duration-200 " +
      (aktif
        ? "bg-purple-950 text-white shadow-md"
        : "bg-white text-zinc-600 ring-1 ring-zinc-200 hover:bg-purple-50 hover:ring-purple-300");
    item.setAttribute("role", "button");
    item.setAttribute("tabindex", "0");
    item.innerHTML = kat.icon + " <span>" + kat.nama + "</span>";

    const pilih = function () {
      state.kategoriAktif = kat.nama;
      renderKategori();
      renderProduk();
    };
    item.addEventListener("click", pilih);
    item.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        pilih();
      }
    });
    kategoriListEl.appendChild(item);
  });
}

function daftarProdukTerfilter() {
  const kata = state.cari.trim().toLowerCase();

  let daftar = state.produk.filter(function (p) {
    const cocokKategori =
      state.kategoriAktif === "Semua" || p.kategori === state.kategoriAktif;
    const teks =
      (p.nama + " " + (p.kategori || "") + " " + (p.deskripsi || "")).toLowerCase();
    const cocokCari = !kata || teks.indexOf(kata) !== -1;
    return cocokKategori && cocokCari;
  });

  if (state.urutan === "termurah") {
    daftar = daftar.slice().sort(function (a, b) {
      return nominalHarga(a.harga) - nominalHarga(b.harga);
    });
  } else if (state.urutan === "termahal") {
    daftar = daftar.slice().sort(function (a, b) {
      return nominalHarga(b.harga) - nominalHarga(a.harga);
    });
  }

  return daftar;
}

function renderProduk() {
  const daftar = daftarProdukTerfilter();
  produkGridEl.innerHTML = "";

  if (daftar.length === 0) {
    produkGridEl.innerHTML =
      '<p class="col-span-full py-16 text-center text-sm text-zinc-400">Tidak ada produk yang cocok dengan pencarianmu.</p>';
    return;
  }

  const fragmen = document.createDocumentFragment();
  daftar.forEach(function (p) {
    const card = document.createElement("article");
    card.className =
      "group flex flex-col overflow-hidden rounded-2xl border border-zinc-100 bg-white shadow-sm transition-all duration-300 hover:scale-[1.02] hover:shadow-xl";

    const img = document.createElement("img");
    img.className =
      "aspect-square w-full bg-zinc-100 object-cover transition-transform duration-500 group-hover:scale-105";
    img.loading = "lazy";
    img.alt = p.nama;
    img.src = p.gambar || PLACEHOLDER;
    img.addEventListener("error", function () {
      img.src = PLACEHOLDER;
    });

    const body = document.createElement("div");
    body.className = "flex flex-1 flex-col gap-1 p-4";

    const kat = document.createElement("span");
    kat.className = "text-[11px] font-semibold uppercase tracking-wider text-purple-700";
    kat.textContent = p.kategori;

    const nama = document.createElement("h2");
    nama.className = "line-clamp-2 text-sm font-bold leading-snug text-zinc-900 md:text-base";
    nama.textContent = p.nama;

    const deskripsi = document.createElement("p");
    deskripsi.className = "line-clamp-2 text-xs leading-relaxed text-zinc-500";
    deskripsi.textContent = p.deskripsi || "";

    const harga = document.createElement("p");
    harga.className = "mt-auto pt-2 font-display text-base font-extrabold text-purple-950 md:text-lg";
    harga.textContent = p.harga;

    const wa = document.createElement("a");
    wa.className =
      "mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-purple-950 px-4 py-2.5 text-sm font-semibold text-white transition-all duration-300 hover:scale-[1.02] hover:bg-purple-800 active:scale-95";
    wa.href =
      "https://wa.me/" +
      waAktif +
      "?text=" +
      encodeURIComponent("Halo, saya ingin memesan: " + p.nama + " (" + p.harga + ")");
    wa.target = "_blank";
    wa.rel = "noopener";
    wa.setAttribute("aria-label", "Pesan " + p.nama + " via WhatsApp");
    wa.innerHTML = WHATSAPP_SVG + "<span>Pesan via WhatsApp</span>";

    body.append(kat, nama, deskripsi, harga, wa);
    card.append(img, body);
    fragmen.appendChild(card);
  });
  produkGridEl.appendChild(fragmen);
}

inputCariEl.addEventListener("input", function (e) {
  state.cari = e.target.value;
  renderProduk();
});

urutHargaEl.addEventListener("change", function (e) {
  state.urutan = e.target.value;
  renderProduk();
});

window.addEventListener("scroll", function () {
  if (window.scrollY > 300) {
    scrollTopBtn.classList.add("show");
  } else {
    scrollTopBtn.classList.remove("show");
  }
});

scrollTopBtn.addEventListener("click", function () {
  window.scrollTo({ top: 0, behavior: "smooth" });
});

(async function init() {
  await muatPengaturan();
  await muatProduk();
})();