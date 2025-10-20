# Super-Simple-File-Browser
A minimalist, read-only PHP file browser with Dark Nord theme, smooth modals, and image gallery support.


# 🗂️ Super Simple File Browser

> **"A minimalist, read-only PHP, One file"**  
> A minimal, fast, and elegant **read-only file browser** written in PHP — with smooth modals, image previews, and a clean Dark-Nord-themed UI.  
> *(Not a file editor — view-only mode for safety.)*

---

## ✨ Features

- 🎨 **Nord-themed UI** — dark, modern design with smooth gradients  
- 📁 **Folder navigation**
- 🧩 **Automatic file type detection** (text, image, archive, audio, video, etc.)  
- 🖼️ **Image previews**  
  - Hover thumbnail preview  
  - Full modal view with smooth animation  
  - Gallery navigation (← → arrows & keyboard support)  
  - “Copy Image URL” button  
- 📝 **Text file viewer** with “Copy text” button  
- ⌨️ **Keyboard shortcuts**:  
  - `Esc` → close modals  
  - `←` / `→` → navigate images  
- 🚫 **Excludes** itself (`index.php`, `ftp.php`, `ssfb.php`) from listings  
- 🔒 **Secure path handling** — prevents directory traversal  
- 💾 **No dependencies** — pure PHP + vanilla JS + CSS  

---

## 🖼️ Screenshot

![preview](https://raw.githubusercontent.com/skippybossx/Super-Simple-File-Browser/refs/heads/main/Super-Simple-File-Browser.png)  


---

## ⚙️ Requirements

- PHP **7.4+** (works with PHP 8.x)  
- Read access to the filesystem  
- No external libraries required  

---

## 📦 Installation

1. Copy `ssfb.php` to any directory on your web server. (change name to index.php if web server not configured to accept ssfb.php files)  
2. Open the directory in your browser, e.g.:  http://localhost/ or http://localhost/directorywithfile/
3. Done 🎉 — the browser works instantly.

---

## Docker Compose

```bash
Install docker: bash <(curl https://get.docker.com)
```
```bash
wget https://raw.githubusercontent.com/skippybossx/Super-Simple-File-Browser/refs/heads/main/docker-compose.yml
```
```bash
docker compose up -d
```

Open the directory in your browser, e.g.:  http://ipofyourserver/

---

## 🧠 Notes

- **Super Simple File Browser** is **read-only**.  
  It is **not a file editor** and does **not** support uploading, deleting, or modifying files — by design.  
- Ideal for static web servers, archives, or personal file listings.  

---


## 📜 License

Released under the **MIT License**.  
You are free to use, modify, and distribute this software for both personal and commercial purposes.

---

## 💡 Author

**Super Simple File Browser**  
Made with ❤️ in PHP and Vanilla JS  
by [skippyboss](https://github.com/skippybossx)
