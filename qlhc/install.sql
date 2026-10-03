-- =====================================================================
--  MODULE QUẢN LÝ HÀNH CHÍNH (QLHC) — Cổng Đào tạo CTUMP
--  Lược đồ CSDL MySQL 5.7+ / MariaDB 10.3+  (utf8mb4)
--
--  Cách dùng:
--   1) Tạo database (vd: qlhc) với collation utf8mb4_unicode_ci
--   2) Import file này bằng phpMyAdmin (tab Import) hoặc:
--        mysql -u USER -p TEN_DB < install.sql
--   3) Đăng nhập: admin / Admin@123  (hệ thống bắt đổi mật khẩu lần đầu)
--
--  Mọi bảng có tiền tố hc_ để dùng chung database với web hiện có.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Đơn vị / bộ phận soạn thảo
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_don_vi (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ten         VARCHAR(255) NOT NULL,
  viet_tat    VARCHAR(30)  NOT NULL DEFAULT '',
  thu_tu      INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Người dùng
--   vai_tro: admin | van_thu | lanh_dao | chuyen_vien
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_nguoi_dung (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ten_dang_nhap   VARCHAR(60)  NOT NULL,
  mat_khau        VARCHAR(255) NOT NULL,
  ho_ten          VARCHAR(150) NOT NULL,
  email           VARCHAR(150) NOT NULL DEFAULT '',
  vai_tro         VARCHAR(20)  NOT NULL DEFAULT 'chuyen_vien',
  don_vi_id       INT UNSIGNED NULL,
  kich_hoat       TINYINT(1)   NOT NULL DEFAULT 1,
  doi_mat_khau    TINYINT(1)   NOT NULL DEFAULT 0,
  dang_nhap_luc   DATETIME NULL,
  tao_luc         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ten_dang_nhap (ten_dang_nhap),
  KEY idx_don_vi (don_vi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Loại văn bản
--   he: hc (NĐ 30/2020) | dang (HD 05-HD/VPTW)
--   co_ten_loai = 0  -> Công văn (không có tên loại trên văn bản)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_loai_van_ban (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  he           VARCHAR(10)  NOT NULL DEFAULT 'hc',
  ten          VARCHAR(100) NOT NULL,
  viet_tat     VARCHAR(20)  NOT NULL DEFAULT '',
  co_ten_loai  TINYINT(1)   NOT NULL DEFAULT 1,
  thu_tu       INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_he (he)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Người ký (mẫu chữ ký để chọn nhanh khi soạn thảo)
--   quyen_han: '' | TM. | KT. | TL. | TUQ. | Q.   (Đảng: T/M, K/T, T/L, Q.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_nguoi_ky (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  he           VARCHAR(10)  NOT NULL DEFAULT 'hc',
  ho_ten       VARCHAR(150) NOT NULL,
  chuc_vu      VARCHAR(150) NOT NULL,
  quyen_han    VARCHAR(10)  NOT NULL DEFAULT '',
  thay_mat     VARCHAR(200) NOT NULL DEFAULT '',
  thu_tu       INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Cài đặt dạng khóa - giá trị
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_cai_dat (
  khoa      VARCHAR(60) NOT NULL,
  gia_tri   TEXT NULL,
  PRIMARY KEY (khoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Bộ đếm cấp số (khóa = phạm vi đánh số, vd: di:hc:2026, den:2026)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_bo_dem (
  khoa      VARCHAR(100) NOT NULL,
  gia_tri   INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (khoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Dự thảo văn bản (soạn bằng công cụ)
--   trang_thai: nhap | trinh_ky | da_duyet | tra_lai | da_ban_hanh
--   du_lieu   : JSON các trường soạn thảo
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_du_thao (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  he              VARCHAR(10)  NOT NULL DEFAULT 'hc',
  loai_id         INT UNSIGNED NULL,
  trich_yeu       VARCHAR(500) NOT NULL DEFAULT '',
  du_lieu         LONGTEXT NULL,
  trang_thai      VARCHAR(20)  NOT NULL DEFAULT 'nhap',
  y_kien          TEXT NULL,
  van_ban_di_id   INT UNSIGNED NULL,
  nguoi_tao       INT UNSIGNED NULL,
  nguoi_duyet     INT UNSIGNED NULL,
  tao_luc         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cap_nhat_luc    DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_trang_thai (trang_thai),
  KEY idx_nguoi_tao (nguoi_tao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Sổ văn bản đi
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_van_ban_di (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  he              VARCHAR(10)  NOT NULL DEFAULT 'hc',
  nam             SMALLINT UNSIGNED NOT NULL,
  so              INT UNSIGNED NOT NULL,
  pham_vi         VARCHAR(100) NOT NULL,
  so_ky_hieu      VARCHAR(100) NOT NULL,
  loai_id         INT UNSIGNED NULL,
  ngay_ban_hanh   DATE NOT NULL,
  trich_yeu       TEXT NOT NULL,
  nguoi_ky        VARCHAR(150) NOT NULL DEFAULT '',
  chuc_vu_ky      VARCHAR(150) NOT NULL DEFAULT '',
  don_vi_soan_id  INT UNSIGNED NULL,
  noi_nhan        TEXT NULL,
  so_ban          INT UNSIGNED NOT NULL DEFAULT 1,
  do_khan         VARCHAR(20)  NOT NULL DEFAULT '',
  tep_tin         VARCHAR(255) NULL,
  ten_tep_goc     VARCHAR(255) NULL,
  du_thao_id      INT UNSIGNED NULL,
  ghi_chu         TEXT NULL,
  nguoi_tao       INT UNSIGNED NULL,
  tao_luc         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pham_vi_so (pham_vi, so),
  KEY idx_so_ky_hieu (so_ky_hieu),
  KEY idx_nam (nam),
  KEY idx_ngay (ngay_ban_hanh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Sổ văn bản đến
--   trang_thai: moi | dang_xu_ly | hoan_thanh
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_van_ban_den (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nam              SMALLINT UNSIGNED NOT NULL,
  so_den           INT UNSIGNED NOT NULL,
  ngay_den         DATE NOT NULL,
  co_quan_gui      VARCHAR(255) NOT NULL,
  so_ky_hieu_goc   VARCHAR(100) NOT NULL DEFAULT '',
  ngay_van_ban     DATE NULL,
  loai_ten         VARCHAR(100) NOT NULL DEFAULT '',
  trich_yeu        TEXT NOT NULL,
  do_khan          VARCHAR(20)  NOT NULL DEFAULT '',
  nguoi_xu_ly_id   INT UNSIGNED NULL,
  don_vi_xu_ly_id  INT UNSIGNED NULL,
  han_xu_ly        DATE NULL,
  y_kien_chi_dao   TEXT NULL,
  ket_qua          TEXT NULL,
  trang_thai       VARCHAR(20) NOT NULL DEFAULT 'moi',
  tep_tin          VARCHAR(255) NULL,
  ten_tep_goc      VARCHAR(255) NULL,
  nguoi_tao        INT UNSIGNED NULL,
  tao_luc          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  hoan_thanh_luc   DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_so_den (nam, so_den),
  KEY idx_trang_thai (trang_thai),
  KEY idx_xu_ly (nguoi_xu_ly_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Thư viện biểu mẫu
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_bieu_mau (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ten           VARCHAR(255) NOT NULL,
  nhom          VARCHAR(100) NOT NULL DEFAULT 'Khác',
  mo_ta         TEXT NULL,
  tep_tin       VARCHAR(255) NOT NULL,
  ten_tep_goc   VARCHAR(255) NOT NULL,
  cong_khai     TINYINT(1) NOT NULL DEFAULT 1,
  luot_tai      INT UNSIGNED NOT NULL DEFAULT 0,
  thu_tu        INT NOT NULL DEFAULT 0,
  nguoi_tao     INT UNSIGNED NULL,
  tao_luc       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cap_nhat_luc  DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_nhom (nhom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Nhật ký thao tác
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hc_nhat_ky (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nguoi_dung_id   INT UNSIGNED NULL,
  hanh_dong       VARCHAR(60)  NOT NULL,
  doi_tuong       VARCHAR(60)  NOT NULL DEFAULT '',
  doi_tuong_id    INT UNSIGNED NULL,
  chi_tiet        TEXT NULL,
  ip              VARCHAR(45)  NOT NULL DEFAULT '',
  thoi_gian       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_thoi_gian (thoi_gian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  DỮ LIỆU MẶC ĐỊNH (sửa lại trong menu Danh mục & Cài đặt)
-- =====================================================================

INSERT IGNORE INTO hc_cai_dat (khoa, gia_tri) VALUES
('co_quan_chu_quan', 'TRƯỜNG ĐẠI HỌC Y DƯỢC CẦN THƠ'),
('ten_co_quan',      'TRUNG TÂM DỊCH VỤ VÀ ĐÀO TẠO THEO NHU CẦU XÃ HỘI'),
('ten_co_quan_thuong', 'Trung tâm Dịch vụ và Đào tạo theo nhu cầu xã hội'),
('viet_tat_co_quan', 'TTDV'),
('dia_danh',         'Cần Thơ'),
('danh_so_hc',       'chung'),
('co_chu_noi_dung',  '14'),
('dang_cap_tren',    'ĐẢNG BỘ TRƯỜNG ĐẠI HỌC Y DƯỢC CẦN THƠ'),
('dang_co_quan',     'CHI BỘ TRUNG TÂM DỊCH VỤ VÀ ĐÀO TẠO THEO NHU CẦU XÃ HỘI'),
('dang_viet_tat',    'CB'),
('dang_nhiem_ky',    '2025-2030'),
('canh_bao_han_ngay','3');

INSERT INTO hc_don_vi (ten, viet_tat, thu_tu) VALUES
('Ban Giám đốc', 'BGĐ', 1),
('Bộ phận Hành chính - Tổng hợp', 'HCTH', 2),
('Bộ phận Đào tạo', 'ĐT', 3),
('Bộ phận Dịch vụ', 'DV', 4),
('Bộ phận Kế toán', 'KT', 5);

-- Văn bản hành chính (Phụ lục III, NĐ 30/2020/NĐ-CP)
INSERT INTO hc_loai_van_ban (he, ten, viet_tat, co_ten_loai, thu_tu) VALUES
('hc','Công văn','',0,1),
('hc','Quyết định','QĐ',1,2),
('hc','Thông báo','TB',1,3),
('hc','Kế hoạch','KH',1,4),
('hc','Báo cáo','BC',1,5),
('hc','Tờ trình','TTr',1,6),
('hc','Giấy mời','GM',1,7),
('hc','Biên bản','BB',1,8),
('hc','Nghị quyết','NQ',1,9),
('hc','Chỉ thị','CT',1,10),
('hc','Quy chế','QC',1,11),
('hc','Quy định','QyĐ',1,12),
('hc','Thông cáo','TC',1,13),
('hc','Hướng dẫn','HD',1,14),
('hc','Chương trình','CTr',1,15),
('hc','Phương án','PA',1,16),
('hc','Đề án','ĐA',1,17),
('hc','Dự án','DA',1,18),
('hc','Công điện','CĐ',1,19),
('hc','Giấy giới thiệu','GGT',1,20),
('hc','Giấy nghỉ phép','GNP',1,21),
('hc','Hợp đồng','HĐ',1,22),
('hc','Bản ghi nhớ','BGN',1,23),
('hc','Bản thỏa thuận','BTT',1,24),
('hc','Giấy ủy quyền','GUQ',1,25),
('hc','Phiếu gửi','PG',1,26),
('hc','Phiếu chuyển','PC',1,27),
('hc','Phiếu báo','PB',1,28),
('hc','Bản sao y','SY',1,29),
('hc','Bản sao lục','SL',1,30),
('hc','Bản trích sao','TrS',1,31);

-- Văn bản của Đảng (HD 05-HD/VPTW)
INSERT INTO hc_loai_van_ban (he, ten, viet_tat, co_ten_loai, thu_tu) VALUES
('dang','Công văn','CV',0,1),
('dang','Nghị quyết','NQ',1,2),
('dang','Quyết định','QĐ',1,3),
('dang','Quy định','QĐ',1,4),
('dang','Chỉ thị','CT',1,5),
('dang','Kết luận','KL',1,6),
('dang','Quy chế','QC',1,7),
('dang','Thông tri','TT',1,8),
('dang','Hướng dẫn','HD',1,9),
('dang','Báo cáo','BC',1,10),
('dang','Kế hoạch','KH',1,11),
('dang','Chương trình','CTr',1,12),
('dang','Thông báo','TB',1,13),
('dang','Tờ trình','TTr',1,14),
('dang','Đề án','ĐA',1,15),
('dang','Biên bản','BB',1,16);

INSERT INTO hc_nguoi_ky (he, ho_ten, chuc_vu, quyen_han, thay_mat, thu_tu) VALUES
('hc',   'Nguyễn Văn A', 'GIÁM ĐỐC',     '',    '',              1),
('hc',   'Trần Thị B',   'PHÓ GIÁM ĐỐC', 'KT.', 'GIÁM ĐỐC',      2),
('dang', 'Nguyễn Văn A', 'BÍ THƯ',       'T/M', 'CHI BỘ',        1);

-- Tài khoản quản trị mặc định: admin / Admin@123 (bắt buộc đổi khi đăng nhập lần đầu)
INSERT IGNORE INTO hc_nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, vai_tro, kich_hoat, doi_mat_khau) VALUES
('admin', '$2y$10$7wJASDUk85JKjDcgyGiK4Oez9knioWY6pe6.JHrMbTNNGKqrZCuZG', 'Quản trị hệ thống', 'admin', 1, 1);
