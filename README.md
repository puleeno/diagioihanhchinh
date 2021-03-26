Địa giới hành chính Việt Nam
=

# Chức năng chính:

- [x] Cập nhật data tự động từ "Tổng cục thống kê - Đơn vị hành chính Việt Nam": https://www.gso.gov.vn/dmhc2015/Default.aspx
- [x] Hỗ trợ convert tự động sang các format khác: JSON
- [x] Import geodata từ https://sites.google.com/site/mapmakervietnam/files/kml
- [ ] Tự động lấy geodata từ website https://vietbando.com
- [ ] Lấy dữ liệu từ http://gis.chinhphu.vn/
- [ ] Tương thích với plugin WooCommerce - thương mại điện tử
- [ ] Tương thích với plugin WordLand - bất động sản

# Tài liệu tham khảo
- Subdivisions of Vietnam: https://en.wikipedia.org/wiki/Subdivisions_of_Vietnam


# Làm thế nào để tương thích plugin địa giới hành chính vào plugins của bạn.

## Các plugin tương thích:

Mặc định plugin diagioihanhchinh đã tương thích với các plugin sau

1. WordLand: Tạo và quản lý Bất động sản
2. Đang cập nhật


Ngoài ra bạn cũng có thể viết code tương thích cho plugin của mình như tài liệu bên dưới
## Custom plugin địa giới hành chính làm việc với plugin của bạn.


```
Diagioihanhchinh::register_location_taxonomy($tax_name, $level = 1);
```
## Với các tham số như sau:

$tax_name: Là taxonomy dùng làm địa danh
$level: Là cấp độ của địa danh hỗ trợ từ 1 đến 3 như sau
  - 1: Cấp tỉnh - thành phố
  - 2: cấp quận - huyện
  - 3: cấp phường - xã
