CLI
=

`diagioihanhchinh` dùng để thực thi các tác vụ của "Địa giới hành chính Việt Nam" qua giao diện dòng lệnh


# Các tính năng được hỗ trợ

- Cập nhật data từ `https://www.gso.gov.vn/dmhc2015/Default.aspx` lưu thành file `Excel`
- Generate ra các format khác từ file Excel tải về:
  + JSON Format

# Generate Data

Để thực hiện generate data bạn thực thi `diagioihanhchinh` với action `generate`

Syntax:

```
./bin/diagioihanhchinh generate --format=<DATA FORMAT>
```

Với DATA FORMAT là các định dạng được hỗ trợ bên dưới. Mặc định là `json`

- JSON

## JSON Format

```
./bin/diagioihanhchinh generate --format=json
```