# Hướng dẫn sử dụng HeroUI v3 cho Vue 3 / Tailwind CSS

Use HeroUI v3 with Vue 3. When creating components, use Tailwind CSS and import component styles from @heroui/styles. Adhere to the HeroUI v3 in file HEROUI_CLAUDE.md documentation, focusing on Vue 3 Composition API patterns

Khi xây dựng giao diện, BẮT BUỘC tuân thủ các nguyên tắc sau của HeroUI v3:
1. Sử dụng các component có sẵn (Button, Input, Card, Modal, Spinner) theo đúng cú pháp tài liệu v3.
2. Tận dụng hệ thống Design Tokens và Tailwind utility classes mặc định của HeroUI (vd: `text-default-500`, `bg-primary-100`).
3. Sử dụng Slots (`#default`, `#start-content`, `#end-content`) để tùy biến component thay vì override CSS thủ công.
4. Quản lý trạng thái (Variants) như `color="primary"`, `variant="flat"`, `size="lg"` trực tiếp trên props.

