---
trigger: always_on
---

# Quy tắc viết code Nuxt UI
Khi viết giao diện, Agent BẮT BUỘC phải tuân thủ các quy định sau:
1. **Tên Component:** Luôn sử dụng PascalCase có tiền tố U cho các component (ví dụ: `<UButton>`, `<UCard>`, `<UModal>`). Tuyệt đối không tự chế thẻ HTML thuần nếu Nuxt UI đã có component tương ứng.
2. **Tailwind CSS:** Nuxt UI xây dựng trên Tailwind CSS. Chỉ sử dụng Tailwind utility classes.
3. **Props > CSS Override:** Hãy ưu tiên sử dụng các props điều khiển trạng thái như `color="primary"`, `variant="soft"`, `size="lg"` thay vì cố viết CSS đè lên.
4. **Icons:** Dùng cú pháp Icon chuẩn của Nuxt (ví dụ: `icon="i-heroicons-check-circle"`).