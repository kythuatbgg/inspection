# Cabinets Management với UI/UX

## Goal
Xây dựng chức năng Cabinets Management hoàn chỉnh với Map View + Import/Export

## Tasks

### Phase 1: Backend - Import/Export API

- [ ] **Task 1:** Tạo Import/Export endpoints trong CabinetController → Verify: Routes exist

### Phase 2: Frontend - Services

- [ ] **Task 2:** Mở rộng cabinetService.js với import/export methods → Verify: Tests pass
- [ ] **Task 3:** Viết tests cho import/export → Verify: 5 tests pass

### Phase 3: UI/UX Design (theo ui-ux-pro-max)

- [ ] **Task 4:** Cập nhật CabinetsView với:
  - Toggle List/Map view
  - Search + Filter
  - Import button
  - Export button
  → Verify: UI matches UX guidelines (touch 56px, accessible)

- [ ] **Task 5:** Tạo CabinetMap component với Leaflet → Verify: Map hiển thị markers

- [ ] **Task 6:** Tạo ImportModal cho upload Excel/CSV → Verify: Modal works

### Phase 4: Implementation

- [ ] **Task 7:** Kết nối API vào Views → Verify: Tất cả features hoạt động

## Done When
- [ ] CRUD cabinets hoạt động
- [ ] Map view hiển thị tủ cáp
- [ ] Import/Export Excel hoạt động
- [ ] UI tuân thủ UX (touch 56px)

## UX Guidelines (ui-ux-pro-max)
- Touch targets: 56px minimum
- Toggle buttons for view switch
- Map markers với popup info
- Progress indicator cho import
- Confirmation dialogs
