-- ============================================================
--  Информационная система учёта кадров - структура и данные
-- ============================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

DROP DATABASE IF EXISTS personnel_db;
CREATE DATABASE personnel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE personnel_db;

-- ----- Пользователи системы --------------------------------------------------

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    login         VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','hr') NOT NULL DEFAULT 'hr',
    full_name     VARCHAR(150) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----- Подразделения (справочник, редактирует admin) -------------------------

CREATE TABLE departments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    color      VARCHAR(20)  NOT NULL DEFAULT '#0d6efd',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----- Должности (справочник, редактирует admin) -----------------------------

CREATE TABLE positions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----- Сотрудники ------------------------------------------------------------

CREATE TABLE employees (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    surname       VARCHAR(80)  NOT NULL,
    first_name    VARCHAR(80)  NOT NULL,
    patronymic    VARCHAR(80)  DEFAULT NULL,
    department_id INT          NOT NULL,
    position_id   INT          NOT NULL,
    salary        DECIMAL(10,2) NOT NULL DEFAULT 0,
    hire_date     DATE         DEFAULT NULL,
    phone         VARCHAR(30)  DEFAULT NULL,
    email         VARCHAR(120) DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_emp_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_emp_pos  FOREIGN KEY (position_id)   REFERENCES positions(id)   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----- Смены -----------------------------------------------------------------

CREATE TABLE shifts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    employee_id   INT  NOT NULL,
    shift_date    DATE NOT NULL,
    department_id INT  NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_date (employee_id, shift_date),
    CONSTRAINT fk_shift_emp  FOREIGN KEY (employee_id)   REFERENCES employees(id)   ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_shift_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_shifts_emp_date ON shifts(employee_id, shift_date);
CREATE INDEX idx_shifts_dept_date ON shifts(department_id, shift_date);

-- ============================================================
--  Демонстрационные данные
-- ============================================================

-- Пользователи: admin / admin123 и hr / hr123
INSERT INTO users (login, password_hash, role, full_name) VALUES
('admin', '$2y$10$taK8QQhF5pyU.BHM6.yPF.S.De6lrd1N/wBHtQfvP6JSjuhUIqaiC', 'admin', 'Администратор системы'),
('hr',    '$2y$10$7bsCpE2TFDm4vE8ReNjDlOem1aH9ku5NmaKLUI8.iEK6epUooodU6', 'hr',    'Специалист по кадрам');

-- Подразделения (admin может править)
INSERT INTO departments (id, name, color) VALUES
(1, 'Производство', '#0d6efd'),
(2, 'Продажи',      '#198754'),
(3, 'Бухгалтерия',  '#ffc107');

-- Должности
INSERT INTO positions (id, name) VALUES
(1, 'Мастер цеха'),
(2, 'Слесарь'),
(3, 'Технолог'),
(4, 'Старший менеджер'),
(5, 'Менеджер по продажам'),
(6, 'Главный бухгалтер'),
(7, 'Бухгалтер');

-- Сотрудники
INSERT INTO employees (id, surname, first_name, patronymic, department_id, position_id, salary, hire_date, phone, email) VALUES
(1, 'Морозов',   'Игорь',     'Иванович',    1, 1, 65000, '2022-03-15', '+7-921-100-10-01', 'morozov@example.ru'),
(2, 'Кузнецова', 'Анна',      'Петровна',    2, 4, 75000, '2021-06-01', '+7-921-100-10-02', 'kuznetsova@example.ru'),
(3, 'Смирнов',   'Дмитрий',   'Викторович',  3, 6, 95000, '2020-01-20', '+7-921-100-10-03', 'smirnov@example.ru'),
(4, 'Васильева', 'Елена',     'Николаевна',  1, 2, 50000, '2023-09-10', '+7-921-100-10-04', 'vasileva@example.ru'),
(5, 'Петров',    'Олег',      'Геннадьевич', 2, 5, 55000, '2022-11-05', '+7-921-100-10-05', 'petrov@example.ru'),
(6, 'Зайцева',   'Мария',     'Юрьевна',     3, 7, 60000, '2023-02-14', '+7-921-100-10-06', 'zayceva@example.ru'),
(7, 'Павленок',  'Иван',      'Павлович',    1, 3, 58000, '2024-04-01', '+7-921-100-10-07', 'pavlenok@example.ru');

-- Смены за апрель 2026.
INSERT INTO shifts (employee_id, shift_date, department_id) VALUES
(1, '2026-04-01', 1), (1, '2026-04-03', 1), (1, '2026-04-05', 1),
(1, '2026-04-06', 1), (1, '2026-04-07', 1), (1, '2026-04-09', 2),
(2, '2026-04-02', 2), (2, '2026-04-04', 2), (2, '2026-04-05', 2),
(2, '2026-04-10', 2), (2, '2026-04-13', 3),
(3, '2026-04-07', 3),
(4, '2026-04-02', 1), (4, '2026-04-03', 1), (4, '2026-04-14', 1), (4, '2026-04-15', 1),
(5, '2026-04-04', 2), (5, '2026-04-05', 2), (5, '2026-04-08', 1),
(6, '2026-04-06', 3), (6, '2026-04-13', 3), (6, '2026-04-20', 3),
(7, '2026-04-01', 1), (7, '2026-04-08', 1), (7, '2026-04-15', 1), (7, '2026-04-22', 1);
