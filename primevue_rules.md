# Hướng dẫn sử dụng PrimeVue (Unstyled) + Tailwind CSS cho Mobile PWA
BẮT BUỘC tuân thủ các nguyên tắc sau khi viết UI components:
1. **Unstyled Mode:** Sử dụng PrimeVue ở chế độ Unstyled kết hợp với Tailwind CSS Presets (Aura hoặc Lara). Không import CSS mặc định của PrimeVue.
2. **Pass-Through (pt):** Khi cần can thiệp CSS sâu vào bên trong component của PrimeVue để phóng to vùng chạm, hãy sử dụng thuộc tính `pt` (Pass-Through). Ví dụ: `<Dropdown :pt="{ root: { class: 'min-h-[48px] text-lg' } }" />`.
3. **Form & Validation:** Kết hợp PrimeVue Input components (InputText, RadioButton, Checkbox) với VeeValidate hoặc logic xử lý form nội bộ của Vue 3.
4. **Camera & Image:** Không dùng component FileUpload mặc định của PrimeVue cho việc chụp ảnh hiện trường. Hãy dùng `<input type="file" capture="environment" class="hidden" ref="cameraInput">` và dùng một `<Button @click="triggerCamera">` của PrimeVue để gọi nó lên nhằm đảm bảo UX.