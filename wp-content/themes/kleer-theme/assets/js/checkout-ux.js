/**
 * KLEER Checkout & Cart UX Optimization — Task L03-G5-02
 *
 * 1. Bộ chọn nhanh địa chỉ hành chính 2 cấp theo địa giới mới sau sáp nhập 2025:
 *    - Cấp 1: 34 Tỉnh / Thành phố trực thuộc Trung ương (bãi bỏ cấp Quận/Huyện).
 *    - Cấp 2: Phường / Xã / Đặc khu trực thuộc Tỉnh / Thành phố
 *      (Tích hợp sẵn đầy đủ 34 Tỉnh/Thành và trọn bộ 65 Phường/Xã/Đặc khu của Tỉnh Khánh Hòa mới
 +      theo Nghị quyết 1667/NQ-UBTVQH15, đồng thời đồng bộ tự động từ Open API v2 provinces.open-api.vn/api/v2).
 * 2. Tự động lưu & điền ngầm thông tin giao hàng từ trình duyệt (localStorage) giúp hoàn tất form < 30 giây.
 * 3. Gợi ý nhanh đuôi Email (@gmail.com, @icloud.com, @outlook.com) và kiểm tra SĐT Việt Nam tức thời.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'kleer_checkout_profile_v2';
    const API_V2_BASE = 'https://provinces.open-api.vn/api/v2';

    /**
     * Danh mục chuẩn 34 Tỉnh / Thành phố sau sáp nhập địa giới hành chính (07/2025)
     * kèm danh sách Phường / Xã / Đặc khu mới (đầy đủ 65/65 đơn vị của Tỉnh Khánh Hòa theo NQ 1667/NQ-UBTVQH15).
     */
    const VN_LOCATIONS_2025 = {
        'Thành phố Hồ Chí Minh': [
            'Phường Sài Gòn', 'Phường Tân Định', 'Phường Bến Thành', 'Phường Cầu Ông Lãnh', 'Phường Bàn Cờ',
            'Phường Xuân Hòa', 'Phường Nhiêu Lộc', 'Phường Xóm Chiếu', 'Phường Khánh Hội', 'Phường Vĩnh Hội',
            'Phường Chợ Quán', 'Phường An Đông', 'Phường Chợ Lớn', 'Phường Bình Tiên', 'Phường Bình Tây',
            'Phường Bình Phú', 'Phường Phú Lâm', 'Phường Tân Thuận', 'Phường Phú Thuận', 'Phường Tân Mỹ',
            'Phường Tân Hưng', 'Phường Chánh Hưng', 'Phường Phú Định', 'Phường Bình Đông', 'Phường Diên Hồng',
            'Phường Vườn Lài', 'Phường Hòa Hưng', 'Phường Minh Phụng', 'Phường Bình Thới', 'Phường Hòa Bình',
            'Phường Phú Thọ', 'Phường Đông Hưng Thuận', 'Phường Trung Mỹ Tây', 'Phường Tân Thới Hiệp',
            'Phường Thới An', 'Phường An Phú Đông', 'Phường Gia Định', 'Phường Bình Thạnh', 'Phường Bình Lợi Trung',
            'Phường Thạnh Mỹ Tây', 'Phường Bình Quới', 'Phường Hạnh Thông', 'Phường An Nhơn', 'Phường Gò Vấp',
            'Phường An Hội Đông', 'Phường Thông Tây Hội', 'Phường An Hội Tây', 'Phường Đức Nhuận', 'Phường Cầu Kiệu',
            'Phường Phú Nhuận', 'Phường Tân Sơn Hòa', 'Phường Tân Sơn Nhất', 'Phường Tân Hòa', 'Phường Bảy Hiền',
            'Phường Tân Bình', 'Phường Tân Sơn', 'Phường Tây Thạnh', 'Phường Tân Sơn Nhì', 'Phường Phú Thọ Hòa',
            'Phường Tân Phú', 'Phường Phú Thạnh', 'Phường Hiệp Bình', 'Phường Thủ Đức', 'Phường Tam Bình',
            'Phường Linh Xuân', 'Phường Tăng Nhơn Phú', 'Phường Long Bình', 'Phường Long Phước', 'Phường Long Trường',
            'Phường Cát Lái', 'Phường Bình Trưng', 'Phường Phước Long', 'Phường An Khánh', 'Phường Thủ Dầu Một',
            'Phường Phú Lợi', 'Phường Bình Dương', 'Phường Thuận An', 'Phường Dĩ An', 'Phường Đông Hòa',
            'Phường Vũng Tàu', 'Phường Tam Thắng', 'Phường Rạch Dừa', 'Phường Bà Rịa', 'Đặc khu Côn Đảo'
        ],
        'Thành phố Hà Nội': [
            'Phường Hoàn Kiếm', 'Phường Cửa Nam', 'Phường Ba Đình', 'Phường Ngọc Hà', 'Phường Giảng Võ',
            'Phường Hai Bà Trưng', 'Phường Vĩnh Tuy', 'Phường Bạch Mai', 'Phường Đống Đa', 'Phường Kim Liên',
            'Phường Văn Miếu - Quốc Tử Giám', 'Phường Láng', 'Phường Ô Chợ Dừa', 'Phường Hồng Hà', 'Phường Tây Hồ',
            'Phường Phú Thượng', 'Phường Cầu Giấy', 'Phường Nghĩa Đô', 'Phường Yên Hòa', 'Phường Thanh Xuân',
            'Phường Khương Đình', 'Phường Phương Liệt', 'Phường Hoàng Mai', 'Phường Vĩnh Hưng', 'Phường Tương Mai',
            'Phường Định Công', 'Phường Hoàng Liệt', 'Phường Yên Sở', 'Phường Long Biên', 'Phường Bồ Đề',
            'Phường Việt Hưng', 'Phường Phúc Lợi', 'Phường Hà Đông', 'Phường Dương Nội', 'Phường Yên Nghĩa',
            'Phường Từ Liêm', 'Phường Tây Tựu', 'Phường Xuân Phương', 'Phường Mỹ Đình', 'Phường Tây Mỗ', 'Phường Đại Mỗ'
        ],
        'Tỉnh Khánh Hòa': [
            // 16 Phường mới theo Nghị quyết 1667/NQ-UBTVQH15
            'Phường Nha Trang',
            'Phường Bắc Nha Trang',
            'Phường Tây Nha Trang',
            'Phường Nam Nha Trang',
            'Phường Cam Ranh',
            'Phường Bắc Cam Ranh',
            'Phường Cam Linh',
            'Phường Ba Ngòi',
            'Phường Ninh Hòa',
            'Phường Đông Ninh Hòa',
            'Phường Hòa Thắng',
            'Phường Phan Rang',
            'Phường Bảo An',
            'Phường Đô Vinh',
            'Phường Đông Hải',
            'Phường Ninh Chử',
            // 48 Xã mới theo Nghị quyết 1667/NQ-UBTVQH15 (gồm cả Khánh Hòa cũ & Ninh Thuận sáp nhập)
            'Xã Nam Cam Ranh',
            'Xã Bắc Ninh Hòa',
            'Xã Tân Định',
            'Xã Nam Ninh Hòa',
            'Xã Tây Ninh Hòa',
            'Xã Hòa Trí',
            'Xã Đại Lãnh',
            'Xã Tu Bông',
            'Xã Vạn Thắng',
            'Xã Vạn Ninh',
            'Xã Vạn Hưng',
            'Xã Diên Khánh',
            'Xã Diên Lạc',
            'Xã Diên Điền',
            'Xã Diên Lâm',
            'Xã Diên Thọ',
            'Xã Suối Hiệp',
            'Xã Cam Lâm',
            'Xã Suối Dầu',
            'Xã Cam Hiệp',
            'Xã Cam An',
            'Xã Khánh Vĩnh',
            'Xã Bắc Khánh Vĩnh',
            'Xã Trung Khánh Vĩnh',
            'Xã Tây Khánh Vĩnh',
            'Xã Nam Khánh Vĩnh',
            'Xã Khánh Sơn',
            'Xã Đông Khánh Sơn',
            'Xã Tây Khánh Sơn',
            'Xã Ninh Hải',
            'Xã Xuân Hải',
            'Xã Vĩnh Hải',
            'Xã Thuận Bắc',
            'Xã Công Hải',
            'Xã Ninh Phước',
            'Xã Phước Hậu',
            'Xã Phước Hữu',
            'Xã Thuận Nam',
            'Xã Phước Dinh',
            'Xã Cà Ná',
            'Xã Phước Hà',
            'Xã Ninh Sơn',
            'Xã Lâm Sơn',
            'Xã Mỹ Sơn',
            'Xã Anh Dũng',
            'Xã Bác Ái',
            'Xã Bác Ái Đông',
            'Xã Bác Ái Tây',
            // 01 Đặc khu
            'Đặc khu Trường Sa'
        ],
        'Thành phố Đà Nẵng': [
            'Phường Hải Châu', 'Phường Hòa Cường', 'Phường Thanh Khê', 'Phường An Khê', 'Phường An Hải',
            'Phường Sơn Trà', 'Phường Ngũ Hành Sơn', 'Phường Hòa Quý', 'Phường Liên Chiểu', 'Phường Hòa Khánh',
            'Phường Cẩm Lệ', 'Phường Hòa Xuân', 'Phường Tam Kỳ', 'Phường Hội An', 'Phường Điện Bàn',
            'Xã Hòa Vang', 'Xã Hòa Tiến', 'Xã Bà Nà', 'Đặc khu Hoàng Sa'
        ],
        'Thành phố Cần Thơ': [
            'Phường Ninh Kiều', 'Phường Cái Khế', 'Phường Tân An', 'Phường An Bình', 'Phường Bình Thủy',
            'Phường Long Tuyền', 'Phường Cái Răng', 'Phường Hưng Phú', 'Phường Ô Môn', 'Phường Thốt Nốt',
            'Phường Vị Thanh', 'Phường Ngã Bảy', 'Phường Sóc Trăng', 'Phường Vĩnh Châu', 'Phường Ngã Năm'
        ],
        'Thành phố Hải Phòng': [
            'Phường Hồng Bàng', 'Phường Ngô Quyền', 'Phường Lê Chân', 'Phường Hải An', 'Phường Kiến An',
            'Phường Đồ Sơn', 'Phường Dương Kinh', 'Phường Thủy Nguyên', 'Phường An Dương', 'Phường Hải Dương',
            'Phường Chí Linh', 'Phường Kinh Môn', 'Đặc khu Cát Hải', 'Đặc khu Bạch Long Vĩ'
        ],
        'Thành phố Huế': [
            'Phường Thuận Hóa', 'Phường Phú Xuân', 'Phường Vỹ Dạ', 'Phường An Cựu', 'Phường Thủy Xuân',
            'Phường Kim Long', 'Phường Hương Thủy', 'Phường Hương Trà', 'Phường Phong Điền', 'Xã Phú Vang', 'Xã Phú Lộc'
        ],
        'Tỉnh Đồng Nai': [
            'Phường Biên Hòa', 'Phường Trấn Biên', 'Phường Tam Hiệp', 'Phường Long Bình', 'Phường Hố Nai',
            'Phường Long Khánh', 'Phường Đồng Xoài', 'Phường Bình Long', 'Phường Phước Long', 'Xã Long Thành', 'Xã Nhơn Trạch', 'Xã Trảng Bom'
        ],
        'Tỉnh Lâm Đồng': [
            'Phường Xuân Hương - Đà Lạt', 'Phường Cam Ly - Đà Lạt', 'Phường Lâm Viên - Đà Lạt', 'Phường Xuân Trường - Đà Lạt',
            'Phường Lang Biang - Đà Lạt', 'Phường Bảo Lộc', 'Phường Phan Thiết', 'Phường Mũi Né', 'Phường Phú Thủy',
            'Phường La Gi', 'Phường Gia Nghĩa', 'Xã Đức Trọng', 'Xã Di Linh', 'Xã Đơn Dương', 'Đặc khu Phú Quý'
        ],
        'Tỉnh Đắk Lắk': [
            'Phường Buôn Ma Thuột', 'Phường Tân An', 'Phường Tân Lập', 'Phường Thành Nhất', 'Phường Ea Kao',
            'Phường Buôn Hồ', 'Phường Tuy Hòa', 'Phường Phú Yên', 'Phường Sông Cầu', 'Phường Đông Hòa', 'Xã Ea Kar', 'Xã Krông Pắc'
        ],
        'Tỉnh Gia Lai': [
            'Phường Pleiku', 'Phường Diên Hồng', 'Phường Hội Phú', 'Phường Thống Nhất', 'Phường An Khê',
            'Phường Ayun Pa', 'Phường Kon Tum', 'Xã Chư Sê', 'Xã Đak Đoa', 'Xã Ngọc Hồi'
        ],
        'Tỉnh Quảng Ngãi': [
            'Phường Cẩm Thành', 'Phường Nghĩa Lộ', 'Phường Trương Quang Trọng', 'Phường Đức Phổ', 'Phường Quy Nhơn',
            'Phường Quy Nhơn Nam', 'Phường Quy Nhơn Bắc', 'Phường An Nhơn', 'Phường Hoài Nhơn', 'Xã Bình Sơn', 'Đặc khu Lý Sơn'
        ],
        'Tỉnh Quảng Trị': [
            'Phường Đông Hà', 'Phường Nam Đông Hà', 'Phường Quảng Trị', 'Phường Đồng Hới', 'Phường Ba Đồn',
            'Xã Vĩnh Linh', 'Xã Gio Linh', 'Xã Cam Lộ', 'Xã Hướng Hóa', 'Xã Lệ Thủy', 'Xã Quảng Ninh', 'Đặc khu Cồn Cỏ'
        ],
        'Tỉnh Hà Tĩnh': [
            'Phường Thành Sen', 'Phường Trần Phú', 'Phường Hà Huy Tập', 'Phường Hồng Lĩnh', 'Phường Kỳ Anh',
            'Xã Thạch Hà', 'Xã Cẩm Xuyên', 'Xã Can Lộc', 'Xã Nghi Xuân', 'Xã Hương Sơn', 'Xã Hương Khê'
        ],
        'Tỉnh Nghệ An': [
            'Phường Trường Vinh', 'Phường Thành Vinh', 'Phường Vinh Phú', 'Phường Vinh Lộc', 'Phường Cửa Lò',
            'Phường Hoàng Mai', 'Phường Thái Hòa', 'Xã Hưng Nguyên', 'Xã Nam Đàn', 'Xã Nghi Lộc', 'Xã Diễn Châu', 'Xã Quỳnh Lưu'
        ],
        'Tỉnh Thanh Hóa': [
            'Phường Hạc Thành', 'Phường Quảng Phú', 'Phường Đông Quang', 'Phường Đông Sơn', 'Phường Đông Tiến',
            'Phường Hàm Rồng', 'Phường Nguyệt Viên', 'Phường Sầm Sơn', 'Phường Bỉm Sơn', 'Phường Nghi Sơn', 'Xã Hoằng Hóa', 'Xã Quảng Xương'
        ],
        'Tỉnh Ninh Bình': [
            'Phường Hoa Lư', 'Phường Nam Hoa Lư', 'Phường Tây Hoa Lư', 'Phường Đông Hoa Lư', 'Phường Tam Điệp',
            'Phường Nam Định', 'Phường Thiên Trường', 'Phường Phủ Lý', 'Phường Duy Tiên', 'Xã Gia Viễn', 'Xã Yên Khánh'
        ],
        'Tỉnh Hưng Yên': [
            'Phường Phố Hiến', 'Phường Sơn Nam', 'Phường Hồng Châu', 'Phường Mỹ Hào', 'Phường Thái Bình',
            'Phường Trà Lý', 'Xã Văn Giang', 'Xã Văn Lâm', 'Xã Yên Mỹ', 'Xã Khoái Châu'
        ],
        'Tỉnh Bắc Ninh': [
            'Phường Kinh Bắc', 'Phường Vũ Ninh', 'Phường Võ Cường', 'Phường Hạp Lĩnh', 'Phường Từ Sơn',
            'Phường Thuận Thành', 'Phường Quế Võ', 'Phường Bắc Giang', 'Phường Việt Yên', 'Phường Chũ', 'Xã Yên Phong', 'Xã Tiên Du'
        ],
        'Tỉnh Quảng Ninh': [
            'Phường Hạ Long', 'Phường Hồng Gai', 'Phường Bãi Cháy', 'Phường Tuần Châu', 'Phường Cẩm Phả',
            'Phường Uông Bí', 'Phường Móng Cái', 'Phường Đông Triều', 'Phường Quảng Yên', 'Đặc khu Vân Đồn', 'Đặc khu Cô Tô'
        ],
        'Tỉnh Phú Thọ': [
            'Phường Việt Trì', 'Phường Nông Trang', 'Phường Thanh Miếu', 'Phường Phú Thọ', 'Phường Vĩnh Yên',
            'Phường Phúc Yên', 'Phường Hòa Bình', 'Xã Lâm Thao', 'Xã Phù Ninh', 'Xã Tam Đảo'
        ],
        'Tỉnh Thái Nguyên': [
            'Phường Phan Đình Phùng', 'Phường Gia Sàng', 'Phường Quyết Thắng', 'Phường Tích Lương', 'Phường Phổ Yên',
            'Phường Sông Công', 'Phường Bắc Kạn', 'Xã Đại Từ', 'Xã Phú Bình', 'Xã Định Hóa'
        ],
        'Tỉnh Tuyên Quang': [
            'Phường Minh Xuân', 'Phường Mỹ Lâm', 'Phường An Tường', 'Phường Nông Tiến', 'Phường Hà Giang',
            'Xã Sơn Dương', 'Xã Yên Sơn', 'Xã Hàm Yên', 'Xã Vị Xuyên', 'Xã Đồng Văn', 'Xã Mèo Vạc'
        ],
        'Tỉnh Lào Cai': [
            'Phường Lào Cai', 'Phường Cam Đường', 'Phường Sa Pa', 'Phường Yên Bái', 'Phường Nghĩa Lộ',
            'Xã Bảo Thắng', 'Xã Bát Xát', 'Xã Bắc Hà', 'Xã Văn Yên', 'Xã Lục Yên'
        ],
        'Tỉnh Cao Bằng': [
            'Phường Thục Phán', 'Phường Nùng Trí Cao', 'Phường Tân Giang', 'Xã Hòa An', 'Xã Quảng Hòa', 'Xã Trùng Khánh'
        ],
        'Tỉnh Lạng Sơn': [
            'Phường Đông Kinh', 'Phường Lương Văn Tri', 'Phường Kỳ Lừa', 'Phường Tam Thanh', 'Xã Cao Lộc', 'Xã Hữu Lũng', 'Xã Lộc Bình'
        ],
        'Tỉnh Sơn La': [
            'Phường Tô Hiệu', 'Phường Chiềng An', 'Phường Chiềng Cơi', 'Phường Chiềng Sinh', 'Phường Mộc Châu',
            'Phường Thảo Nguyên', 'Xã Mai Sơn', 'Xã Yên Châu', 'Xã Thuận Châu'
        ],
        'Tỉnh Điện Biên': [
            'Phường Điện Biên Phủ', 'Phường Mường Thanh', 'Phường Mường Lay', 'Xã Điện Biên', 'Xã Tuần Giáo', 'Xã Mường Ảng'
        ],
        'Tỉnh Lai Châu': [
            'Phường Tân Phong', 'Phường Đoàn Kết', 'Xã Tam Đường', 'Xã Phong Thổ', 'Xã Than Uyên', 'Xã Tân Uyên'
        ],
        'Tỉnh Tây Ninh': [
            'Phường Tân Ninh', 'Phường Bình Minh', 'Phường Hòa Thành', 'Phường Trảng Bàng', 'Phường Gò Dầu',
            'Phường Long An', 'Phường Tân An', 'Phường Kiến Tường', 'Xã Bến Lức', 'Xã Đức Hòa', 'Xã Cần Giuộc'
        ],
        'Tỉnh Đồng Tháp': [
            'Phường Cao Lãnh', 'Phường Mỹ Ngãi', 'Phường Sa Đéc', 'Phường Hồng Ngự', 'Phường Mỹ Tho',
            'Phường Đạo Thạnh', 'Phường Gò Công', 'Phường Cai Lậy', 'Xã Lấp Vò', 'Xã Châu Thành', 'Xã Cái Bè'
        ],
        'Tỉnh Vĩnh Long': [
            'Phường Long Châu', 'Phường Phước Hậu', 'Phường Tân Hạnh', 'Phường Bình Minh', 'Phường Bến Tre',
            'Phường Phú Khương', 'Phường Trà Vinh', 'Phường Duyên Hải', 'Xã Long Hồ', 'Xã Mang Thít', 'Xã Mỏ Cày'
        ],
        'Tỉnh An Giang': [
            'Phường Long Xuyên', 'Phường Mỹ Thới', 'Phường Bình Đức', 'Phường Châu Đốc', 'Phường Tân Châu',
            'Phường Rạch Giá', 'Phường Hà Tiên', 'Xã Chợ Mới', 'Xã Thoại Sơn', 'Đặc khu Phú Quốc', 'Đặc khu Kiên Hải', 'Đặc khu Thổ Châu'
        ],
        'Tỉnh Cà Mau': [
            'Phường An Xuyên', 'Phường Tân Thành', 'Phường Lý Văn Lâm', 'Phường Hòa Thành', 'Phường Bạc Liêu',
            'Phường Giá Rai', 'Xã Năm Căn', 'Xã Ngọc Hiển', 'Xã Trần Văn Thời', 'Xã Cái Nước', 'Xã Vĩnh Lợi'
        ]
    };

    const TRACKED_FIELDS = [
        'billing_first_name',
        'billing_phone',
        'billing_email',
        'billing_state',
        'billing_city',
        'billing_address_1'
    ];

    // Bộ nhớ đệm kết quả gọi API v2 theo mã tỉnh
    const apiProvinceMap = {};
    const apiWardsCache = {};

    /**
     * Đọc hồ sơ giao hàng đã lưu từ localStorage
     */
    function getSavedProfile() {
        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (err) {
            return null;
        }
    }

    /**
     * Lưu ngầm hồ sơ giao hàng vào localStorage
     */
    function saveProfileToStorage() {
        try {
            const current = getSavedProfile() || {};
            let hasAnyValue = false;

            TRACKED_FIELDS.forEach(function (fieldId) {
                const input = document.getElementById(fieldId);
                if (input && input.value.trim() !== '') {
                    current[fieldId] = input.value.trim();
                    hasAnyValue = true;
                }
            });

            const provSelect = document.getElementById('kleer-select-province');
            const wardSelect = document.getElementById('kleer-select-ward');
            if (provSelect && provSelect.value) current._province = provSelect.value;
            if (wardSelect && wardSelect.value) current._ward = wardSelect.value;

            if (hasAnyValue) {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(current));
            }
        } catch (err) {
            // Bỏ qua nếu trình duyệt chặn localStorage
        }
    }

    /**
     * Khởi tạo bộ chọn địa chỉ hành chính 2 cấp sau sáp nhập (34 Tỉnh/Thành phố -> Phường/Xã/Đặc khu)
     */
    function initAddressPicker() {
        const provSelect = document.getElementById('kleer-select-province');
        const wardSelect = document.getElementById('kleer-select-ward');
        const stateInput = document.getElementById('billing_state');
        const cityInput = document.getElementById('billing_city');
        const addrInput = document.getElementById('billing_address_1');

        if (!provSelect || !wardSelect || !stateInput || !cityInput) {
            return;
        }

        // 1. Nạp danh sách 34 Tỉnh / Thành phố sau sáp nhập
        Object.keys(VN_LOCATIONS_2025).forEach(function (provinceName) {
            const opt = document.createElement('option');
            opt.value = provinceName;
            opt.textContent = provinceName;
            provSelect.appendChild(opt);
        });

        // Đồng bộ danh sách mã tỉnh từ Open API v2 (nếu có mạng) để lấy toàn bộ Phường/Xã mới nhất
        fetch(API_V2_BASE + '/p/')
            .then(function (res) {
                return res.ok ? res.json() : null;
            })
            .then(function (list) {
                if (!Array.isArray(list)) return;
                list.forEach(function (item) {
                    if (item && item.name && item.code) {
                        apiProvinceMap[item.name] = item.code;
                    }
                });
            })
            .catch(function () {
                // Sử dụng dữ liệu tích hợp sẵn khi offline
            });

        function renderWardOptions(wardsList, selectedWard) {
            wardSelect.innerHTML = '<option value="">-- Chọn Phường / Xã / Đặc khu --</option>';

            if (!Array.isArray(wardsList) || wardsList.length === 0) {
                wardSelect.disabled = true;
                return;
            }

            wardsList.forEach(function (wardName) {
                const opt = document.createElement('option');
                opt.value = wardName;
                opt.textContent = wardName;
                if (selectedWard && selectedWard === wardName) {
                    opt.selected = true;
                }
                wardSelect.appendChild(opt);
            });
            wardSelect.disabled = false;
        }

        function populateWards(provinceName, selectedWard) {
            if (!provinceName) {
                renderWardOptions([], '');
                return;
            }

            const localWards = VN_LOCATIONS_2025[provinceName] || [];
            // Hiển thị ngay lập tức từ dữ liệu chuẩn tích hợp sẵn (đặc biệt Khánh Hòa đã có đủ 65/65 đơn vị)
            renderWardOptions(localWards, selectedWard);

            // Nếu tỉnh khác cần nạp đầy đủ từ Open API v2 thì bổ sung bất đồng bộ
            if (provinceName !== 'Tỉnh Khánh Hòa' && apiProvinceMap[provinceName]) {
                const code = apiProvinceMap[provinceName];
                if (apiWardsCache[code]) {
                    renderWardOptions(apiWardsCache[code], wardSelect.value || selectedWard);
                    return;
                }

                fetch(API_V2_BASE + '/p/' + code + '?depth=2')
                    .then(function (res) {
                        return res.ok ? res.json() : null;
                    })
                    .then(function (data) {
                        if (data && Array.isArray(data.wards) && data.wards.length > 0) {
                            const names = data.wards.map(function (w) {
                                return w.name;
                            });
                            apiWardsCache[code] = names;
                            if (provSelect.value === provinceName) {
                                renderWardOptions(names, wardSelect.value || selectedWard);
                            }
                        }
                    })
                    .catch(function () {});
            }
        }

        function syncToCheckoutFields() {
            const prov = provSelect.value;
            const ward = wardSelect.value;

            if (prov) {
                stateInput.value = prov;
                stateInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (ward) {
                cityInput.value = ward;
                cityInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            saveProfileToStorage();

            if (window.jQuery) {
                window.jQuery(document.body).trigger('update_checkout');
            }
        }

        provSelect.addEventListener('change', function () {
            populateWards(provSelect.value, '');
            syncToCheckoutFields();
        });

        wardSelect.addEventListener('change', function () {
            syncToCheckoutFields();
            if (addrInput && addrInput.value.trim() === '') {
                addrInput.focus();
            }
        });

        // Khôi phục trạng thái dropdown nếu có trong profile lưu trữ
        const saved = getSavedProfile();
        if (saved && saved._province && VN_LOCATIONS_2025[saved._province]) {
            provSelect.value = saved._province;
            populateWards(saved._province, saved._ward || '');
        } else if (stateInput.value && VN_LOCATIONS_2025[stateInput.value.trim()]) {
            provSelect.value = stateInput.value.trim();
            populateWards(stateInput.value.trim(), cityInput.value.trim());
        }
    }

    /**
     * Tự động lưu & điền ngầm thông tin từ localStorage (không hiển thị banner rườm rà)
     */
    function initSilentAutoFill() {
        const saved = getSavedProfile();
        if (saved) {
            TRACKED_FIELDS.forEach(function (fieldId) {
                const input = document.getElementById(fieldId);
                if (input && saved[fieldId] && input.value.trim() === '') {
                    input.value = saved[fieldId];
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        TRACKED_FIELDS.forEach(function (fieldId) {
            const input = document.getElementById(fieldId);
            if (!input) return;

            input.addEventListener('change', saveProfileToStorage);
            input.addEventListener('blur', saveProfileToStorage);
        });
    }

    /**
     * Gợi ý nhanh đuôi Email (@gmail.com, @icloud.com, @outlook.com) & Kiểm tra SĐT Việt Nam tức thời
     */
    function initEmailChipsAndValidation() {
        const emailInput = document.getElementById('billing_email');
        const phoneInput = document.getElementById('billing_phone');

        if (emailInput && emailInput.parentElement) {
            const chipWrap = document.createElement('div');
            chipWrap.className = 'kleer-email-chips';
            const domains = ['@gmail.com', '@icloud.com', '@outlook.com', '@yahoo.com'];

            domains.forEach(function (domain) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kleer-email-chips__btn';
                btn.textContent = domain;
                btn.addEventListener('click', function () {
                    const currentVal = emailInput.value.trim();
                    const localPart = currentVal.split('@')[0] || 'khachhang';
                    emailInput.value = localPart + domain;
                    emailInput.dispatchEvent(new Event('input', { bubbles: true }));
                    emailInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
                chipWrap.appendChild(btn);
            });

            emailInput.parentElement.appendChild(chipWrap);
        }

        if (phoneInput && phoneInput.parentElement) {
            const hint = document.createElement('span');
            hint.className = 'kleer-field-feedback';
            hint.setAttribute('aria-live', 'polite');
            phoneInput.parentElement.appendChild(hint);

            phoneInput.addEventListener('input', function () {
                const cleaned = phoneInput.value.replace(/[\s\.\-\(\)]+/g, '');
                if (cleaned === '') {
                    hint.textContent = '';
                    hint.className = 'kleer-field-feedback';
                    phoneInput.classList.remove('kleer-input--valid', 'kleer-input--invalid');
                    return;
                }

                const isValidVNPhone = /^(0|\+?84)(3|5|7|8|9)[0-9]{8}$|^02[0-9]{9}$/.test(cleaned);
                if (isValidVNPhone) {
                    hint.textContent = '✓ Số điện thoại hợp lệ';
                    hint.className = 'kleer-field-feedback kleer-field-feedback--valid';
                    phoneInput.classList.add('kleer-input--valid');
                    phoneInput.classList.remove('kleer-input--invalid');
                } else if (cleaned.length >= 9) {
                    hint.textContent = 'Vui lòng nhập SĐT Việt Nam 10 số (đầu 03, 05, 07, 08, 09)';
                    hint.className = 'kleer-field-feedback kleer-field-feedback--invalid';
                    phoneInput.classList.add('kleer-input--invalid');
                    phoneInput.classList.remove('kleer-input--valid');
                } else {
                    hint.textContent = '';
                    hint.className = 'kleer-field-feedback';
                    phoneInput.classList.remove('kleer-input--valid', 'kleer-input--invalid');
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSilentAutoFill();
        initAddressPicker();
        initEmailChipsAndValidation();
    });
})();
