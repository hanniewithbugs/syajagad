# TODO - Role-based Dashboard Routing (Admin & Santri)

- [x] Update routes:
  - [x] Add `/dbAdmin` route to `dbAdmin` view (auth protected)
  - [x] Add `/dbSantri` route to `dbSantri` view (auth protected)
  - [x] Add role guard logic for each dashboard route

- [x] Update authentication controller:
  - [x] Validate role on login
  - [x] Redirect by role after login (`admin -> /dbAdmin`, `santri -> /dbSantri`)
  - [x] Validate register fields to match form
  - [x] Save role/profile fields on register
  - [x] Redirect by role after register

- [x] Update user persistence:
  - [x] Create migration to add `role`, `nis`, `username`, `tgl_lahir`, `alamat` columns
  - [x] Update `User` model fillable fields

- [x] Fix Blade asset paths:
  - [x] Update `dbAdmin.blade.php` CSS/JS/image references to `asset(...)`

- [ ] Testing (maksimal):
  - [ ] Run migration
  - [ ] Test register admin
  - [ ] Test register santri
  - [ ] Test login admin -> `/dbAdmin`
  - [ ] Test login santri -> `/dbSantri`
  - [ ] Test access protection (wrong role cannot access other dashboard)
  - [ ] Test existing pages (`/`, `/login`, `/register`) still work
