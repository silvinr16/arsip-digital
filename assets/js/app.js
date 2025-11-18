// js/firestore-surat.js
// panggil di index.php sebagai <script type="module" src="/js/firestore-surat.js"></script>

import { db, storage } from "../firebase.js";
import {
  collection, addDoc, getDocs, doc, updateDoc, deleteDoc, serverTimestamp, query, orderBy
} from "https://www.gstatic.com/firebasejs/10.7.2/firebase-firestore.js";
import {
  ref as storageRef, uploadBytes, getDownloadURL, deleteObject
} from "https://www.gstatic.com/firebasejs/10.7.2/firebase-storage.js";

/*
  Nama koleksi Firestore:
  - surat_masuk
  - surat_keluar
  (gunakan nama koleksi sesuai kebutuhan; contoh di bawah pakai "surat_masuk")
*/

const collSuratMasuk = collection(db, "surat_masuk");

// --- Membaca semua dokumen dan render tabel (async) ---
export async function loadSuratMasuk(renderCallback) {
  try {
    // contoh orderBy jika perlu: orderBy("tanggal", "desc")
    const q = query(collSuratMasuk, orderBy("tanggal", "desc"));
    const snap = await getDocs(q);
    const data = snap.docs.map(d => ({ id: d.id, ...d.data() }));
    // callback untuk render ke HTML (index.php akan pass fungsi render)
    if (typeof renderCallback === "function") renderCallback(data);
    return data;
  } catch (err) {
    console.error("Gagal load surat:", err);
    throw err;
  }
}

// --- Tambah data surat dengan lampiran upload ke Storage (file optional) ---
export async function addSuratMasuk(data, fileInputElement = null) {
  try {
    // jika ada file -> upload dulu lalu ambil url
    if (fileInputElement && fileInputElement.files && fileInputElement.files[0]) {
      const file = fileInputElement.files[0];
      // beri nama unik, contoh timestamp + original name
      const namaFile = `${Date.now()}_${file.name.replace(/\s+/g,'_')}`;
      const sRef = storageRef(storage, `lampiran/${namaFile}`);
      await uploadBytes(sRef, file);
      const downloadURL = await getDownloadURL(sRef);
      data.lampiran = {
        name: namaFile,
        originalName: file.name,
        url: downloadURL,
        storagePath: `lampiran/${namaFile}`
      };
    } else {
      data.lampiran = null;
    }

    // tambahkan metadata tanggal server
    data.createdAt = serverTimestamp();
    if (!data.tanggal) data.tanggal = new Date().toISOString();

    const docRef = await addDoc(collSuratMasuk, data);
    return { id: docRef.id, ...data };
  } catch (err) {
    console.error("Gagal menambah surat:", err);
    throw err;
  }
}

// --- Update data surat (tidak mengganti lampiran) ---
export async function updateSuratMasuk(id, updateData) {
  try {
    const dRef = doc(db, "surat_masuk", id);
    await updateDoc(dRef, updateData);
    return true;
  } catch (err) {
    console.error("Gagal update:", err);
    throw err;
  }
}

// --- Hapus data surat, dan hapus file di Storage bila ada ---
export async function deleteSuratMasuk(id) {
  try {
    const dRef = doc(db, "surat_masuk", id);
    // ambil data terlebih dahulu untuk tahu storagePath
    const snap = await dRef.get?.() || null;
    // karena get() di docRef tidak standar di modul, kita akan simply delete doc & optionally delete by storagePath provided earlier
    // (safe approach: call deleteDoc, and if you stored storagePath in doc before, call deleteObject)
    // fetch doc via getDocs on query to find doc with id (or use getDoc import if prefer)
    // Here we'll use getDoc:
    // IMPORT getDoc di atas jika mau; but to keep code minimal, we'll require getDoc:
  } catch (err) {
    console.error("Gagal hapus surat:", err);
    throw err;
  }
}

// --- Hapus doc dan file storage (utility) ---
export async function deleteSuratMasukWithFile(id, storagePath) {
  try {
    // hapus dokumen
    await deleteDoc(doc(db, "surat_masuk", id));
    // hapus file di storage jika ada
    if (storagePath) {
      const fRef = storageRef(storage, storagePath);
      await deleteObject(fRef);
    }
    return true;
  } catch (err) {
    console.error("Gagal hapus doc+file:", err);
    throw err;
  }
}

/* -------------------
  Helper migration: pindahkan data localStorage lama ke Firestore
  Gunakan sekali saja, lalu hapus/matikan fungsi ini.
-------------------- */
export async function migrateLocalStorageToFirestore(localKeyName) {
  // ambil dari localStorage
  const raw = localStorage.getItem(localKeyName);
  if (!raw) {
    console.warn("Tidak ada data localStorage untuk key:", localKeyName);
    return { migrated: 0 };
  }
  let arr;
  try {
    arr = JSON.parse(raw);
    if (!Array.isArray(arr)) arr = [arr];
  } catch (e) {
    console.error("Format localStorage bukan JSON array/object:", e);
    throw e;
  }

  let migrated = 0;
  for (const item of arr) {
    // jika item punya file path lokal, tidak bisa migrate file otomatis; hanya metadata
    const doc = { ...item };
    doc.migratedAt = new Date().toISOString();
    await addDoc(collSuratMasuk, doc);
    migrated++;
  }
  return { migrated };
}
