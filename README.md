# FindCourt 🏀

## Projekta apraksts

**FindCourt** ir tīmekļa vietne, kas paredzēta basketbola cienītājiem, lai palīdzētu atrast basketbola laukumus Latvijā un uzzināt informāciju par tiem.

Lietotāji var apskatīt basketbola laukumus interaktīvā kartē, meklēt sev piemērotāko laukumu un apskatīt citu lietotāju pievienoto informāciju. Reģistrētie lietotāji var pievienot jaunus basketbola laukumus, norādot to atrašanās vietu, nosaukumu, adresi, aprakstu un pievienojot attēlu.

Katram basketbola laukumam lietotāji var pievienot komentārus, attēlus un vērtējumus no 1 līdz 5 zvaigznēm. Tāpat iespējams izteikt savu viedokli par laukumu, nospiežot pogu **Like** vai **Dislike**.

Lietotāji var saglabāt sev interesējošos basketbola laukumus, lai vēlāk tos viegli atrastu savā profilā. Profilā iespējams pārvaldīt saglabātos un paša pievienotos laukumus, kā arī rediģēt savu profila informāciju.

Tīmekļa vietnē ir pieejama arī iespēja ziņot par neatbilstošiem basketbola laukumiem un komentāriem. Administratoram ir iespēja pārvaldīt pievienotos laukumus, lietotāju atsauksmes un saņemtos ziņojumus.

## Galvenās funkcijas

- Basketbola laukumu apskatīšana interaktīvā kartē.
- Jaunu basketbola laukumu pievienošana.
- Basketbola laukumu meklēšana un filtrēšana.
- Komentāru, attēlu un vērtējumu pievienošana.
- Reakciju **Like** un **Dislike** pievienošana laukumiem.
- Basketbola laukumu saglabāšana lietotāja profilā.
- Savu pievienoto laukumu un atsauksmju pārvaldība.
- Lietotāja reģistrācija, autorizācija un profila rediģēšana.
- Ziņošana par neatbilstošiem laukumiem un komentāriem.
- Administratora panelis laukumu, atsauksmju un ziņojumu pārvaldībai.

## Izmantotās tehnoloģijas

- **PHP** – servera puses programmēšanas valoda.
- **Laravel** – tīmekļa vietnes izstrādes ietvars.
- **MySQL** – datubāze lietotāju, laukumu un atsauksmju glabāšanai.
- **JavaScript** – interaktīvo funkciju nodrošināšanai.
- **HTML un CSS** – tīmekļa vietnes struktūrai un dizainam.
- **Laravel Blade** – dinamisku tīmekļa lapu veidošanai.
- **Leaflet** – interaktīvās kartes attēlošanai.
- **OpenStreetMap** – kartes datu attēlošanai.

## Projekta mērķis

Projekta mērķis ir izveidot ērtu un pārskatāmu tīmekļa vietni, kurā basketbola cienītāji var atrast tuvākos basketbola laukumus, apskatīt to attēlus, iepazīties ar citu lietotāju atsauksmēm un dalīties savā pieredzē.

FindCourt palīdz lietotājiem atrast piemērotu basketbola laukumu arī nepazīstamā pilsētā vai vietā, kur nav zināma laukumu atrašanās vieta.

## Kā uzstādit projektu un palaist to

### 1. Projekta lejupielāde

Atveriet termināli un lejupielādējiet projektu no GitHub:

```bash
git clone https://github.com/edzuskk/FindCourt
```

Pārejiet uz projekta mapi laragon terminālā:

```bash
cd FindCourt
```

### 2. Nepieciešamo bibliotēku instalēšana

Instalējiet Laravel projekta atkarības:

```bash
composer install
```

Ja projektā tiek izmantotas npm pakotnes, instalējiet tās:

```bash
npm install
```

### 3. Vides konfigurēšana

Izveidojiet `.env` failu, nokopējot `.env.example`:

```powershell
Copy-Item .env.example .env
```

Ģenerējiet Laravel lietotnes atslēgu:

```bash
php artisan key:generate
```

### 4. Datubāzes konfigurēšana

Izveidojiet MySQL datubāzi ar nosaukumu `FindCourt`.

Atveriet `.env` failu un norādiet savus datubāzes savienojuma datus:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=FindCourt
DB_USERNAME=root
DB_PASSWORD=
```

**Piezīme:** `DB_USERNAME` un `DB_PASSWORD` jānorāda atbilstoši savai MySQL konfigurācijai.

Ja vēlaties izveidot administratoru ar datubāzes sējēju, `.env` failā iestatiet unikālu administratora e-pastu un drošu paroli:

```env
FINDCOURT_ADMIN_EMAIL=admin@example.com
FINDCOURT_ADMIN_PASSWORD=your-secure-password
FINDCOURT_ADMIN_USERNAME=Admin
```

Pēc tam izpildiet `php artisan db:seed`. Ja administratora e-pasts vai parole nav konfigurēta, administrators netiek izveidots. Sējēju var palaist atkārtoti; tas nemaina esošā administratora paroli.

### 5. Datubāzes migrāciju izpilde

Lai izveidotu nepieciešamās datubāzes tabulas, izpildiet:

```bash
php artisan migrate
```

Laukumu koordinātu migrācija pārbauda, vai datubāzē jau nav precīzu koordinātu dublikātu. Ja tādi ir, migrācija apstājas, nedzēšot ierakstus; pirms atkārtotas palaišanas dublikāti ir jāpārskata un jāatrisina.

### 6. Attēlu glabāšanas konfigurēšana

Lai tīmekļa vietnē tiktu attēloti lietotāju augšupielādētie attēli, izveidojiet simbolisko saiti:

```bash
php artisan storage:link
```

### 7. Tīmekļa vietnes palaišana

Palaidiet Laravel izstrādes serveri:

```bash
php artisan serve
```

Izstrādes laikā Vite aktīvi apkalpo priekšgala resursus; atveriet otru termināli un izpildiet:

```bash
npm run dev
```

Lai izveidotu optimizētu priekšgala versiju, izmantojiet `npm run build`.

Pēc servera palaišanas atveriet pārlūkprogrammu un ievadiet adresi:

http://127.0.0.1:8000

Viss izdarīts!