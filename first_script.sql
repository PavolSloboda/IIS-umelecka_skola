CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('administrator', 'atelier_manager', 'teacher', 'registered_user', 'unregistered_user') NOT NULL,
    atelier_id INT NULL,
    email VARCHAR(255) NOT NULL,
    profile_info TEXT,
    FOREIGN KEY (atelier_id) REFERENCES ateliers(atelier_id)
);

CREATE TABLE ateliers (
    atelier_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT,
    FOREIGN KEY (manager_id) REFERENCES users(user_id)
);

CREATE TABLE device_groups (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE devices (
    device_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    group_id INT,
    atelier_id INT,
    year_of_manufacture YEAR,
    purchase_date DATE,
    image_url VARCHAR(255),
    max_loan_duration INT,
    pickup_location VARCHAR(255),
    pickup_time VARCHAR(50),
    available BOOLEAN DEFAULT TRUE,
    restricted_to_users BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (group_id) REFERENCES device_groups(group_id),
    FOREIGN KEY (atelier_id) REFERENCES ateliers(atelier_id)
);

CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    device_id INT,
    reservation_start DATETIME,
    reservation_end DATETIME,
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (device_id) REFERENCES devices(device_id)
);

CREATE TABLE loans (
    loan_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    device_id INT,
    loan_start DATETIME,
    loan_end DATETIME,
    returned BOOLEAN DEFAULT FALSE,
    status ENUM('ongoing', 'completed', 'overdue') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (device_id) REFERENCES devices(device_id)
);

CREATE TABLE atelier_user_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    atelier_id INT,
    role ENUM('teacher', 'registered_user') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (atelier_id) REFERENCES ateliers(atelier_id)
);

/*Výpis dostupných zařízení pro uživatele*/
SELECT d.device_id, d.name, d.description, d.max_loan_duration, d.pickup_location, d.pickup_time
FROM devices d
LEFT JOIN atelier_user_permissions aup ON d.atelier_id = aup.atelier_id
WHERE d.available = TRUE
AND (d.restricted_to_users = FALSE OR aup.user_id = ?);

/*Zobrazení všech výpůjček uživatele*/
SELECT l.loan_id, d.name, l.loan_start, l.loan_end, l.returned, l.status
FROM loans l
JOIN devices d ON l.device_id = d.device_id
WHERE l.user_id = ?;

/*Rezervace zařízení pro uživatele*/
INSERT INTO reservations (user_id, device_id, reservation_start, reservation_end, status)
VALUES (?, ?, ?, ?, 'pending');

/*Potvrzení výpůjčky zařízení*/
START TRANSACTION;

UPDATE reservations
SET status = 'approved'
WHERE reservation_id = ?;

INSERT INTO loans (user_id, device_id, loan_start, loan_end, status)
SELECT user_id, device_id, reservation_start, reservation_end, 'ongoing'
FROM reservations
WHERE reservation_id = ?;

COMMIT;

/*Zrušení rezervace*/
DELETE FROM reservations
WHERE reservation_id = ? AND status = 'pending';

/*Vrácení zařízení*/
UPDATE loans
SET returned = TRUE, status = 'completed'
WHERE loan_id = ?;

/*Výpis všech zařízení patřících konkrétnímu ateliéru*/
SELECT d.device_id, d.name, d.description, d.available, d.max_loan_duration
FROM devices d
WHERE d.atelier_id = ?;

/*Kontrola dostupnosti zařízení*/
DELIMITER //

CREATE FUNCTION check_device_availability(p_device_id INT, p_start_time DATETIME, p_end_time DATETIME)
RETURNS TINYINT(1)
DETERMINISTIC
BEGIN
    DECLARE available TINYINT(1);

    -- Kontrola dostupnosti zařízení v zadaném časovém rozmezí
    SELECT COUNT(*) = 0 INTO available
    FROM loans
    WHERE device_id = p_device_id
    AND (
        (loan_start <= p_end_time AND loan_end >= p_start_time)
    )
    AND status = 'ongoing';

    RETURN available;
END //

DELIMITER ;



/*Přidání nového zařízení*/
DELIMITER //

CREATE PROCEDURE add_device(
    IN p_name VARCHAR(100), 
    IN p_description TEXT, 
    IN p_group_id INT, 
    IN p_atelier_id INT,
    IN p_year_of_manufacture YEAR,
    IN p_purchase_date DATE,
    IN p_image_url VARCHAR(255),
    IN p_max_loan_duration INT,
    IN p_pickup_location VARCHAR(255),
    IN p_pickup_time VARCHAR(50)
)
BEGIN
    INSERT INTO devices (name, description, group_id, atelier_id, year_of_manufacture, purchase_date, image_url, max_loan_duration, pickup_location, pickup_time, available)
    VALUES (p_name, p_description, p_group_id, p_atelier_id, p_year_of_manufacture, p_purchase_date, p_image_url, p_max_loan_duration, p_pickup_location, p_pickup_time, TRUE);
END;
DELIMITER ;

/*Automatické nastavení dostupnosti po vrácení zařízení*/

DELIMITER //

CREATE TRIGGER after_loan_return
AFTER UPDATE ON loans
FOR EACH ROW
BEGIN
    IF NEW.returned = TRUE THEN
        UPDATE devices
        SET available = TRUE
        WHERE device_id = NEW.device_id;
    END IF;
END;

DELIMITER ;

/*Vytvoření uživatelských účtů*/
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL
);
INSERT INTO roles (role_name) 
VALUES 
('admin'), 
('atelier_manager'), 
('teacher'), 
('registered_user'), 
('guest');

/*Přiřazení role uživateli*/
ALTER TABLE users ADD COLUMN role_id INT;

-- Definice zahraničního klíče mezi uživateli a rolemi
ALTER TABLE users 
ADD CONSTRAINT fk_role
FOREIGN KEY (role_id) 
REFERENCES roles(role_id);

INSERT INTO users (username, password, role_id) 
VALUES ('macko', 'macko', 1);  -- 1 je role admina

/*Definování oprávnění (pouze ukázka konceptu)*/
CREATE TABLE permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL
);
INSERT INTO permissions (permission_name) 
VALUES 
('manage_users'), 
('manage_ateliers'), 
('manage_devices'), 
('loan_device'), 
('reserve_device'), 
('view_available_devices');

/*Přiřazení oprávnění k rolím*/
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
);
-- Administrátor má všechna oprávnění
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions;

-- Správce ateliéru může spravovat ateliér a zařízení
INSERT INTO role_permissions (role_id, permission_id) 
VALUES (2, 2), (2, 3), (2, 6);

-- Vyučující může spravovat zařízení a výpůjčky
INSERT INTO role_permissions (role_id, permission_id) 
VALUES (3, 3), (3, 4), (3, 5), (3, 6);

-- Registrovaný uživatel může si rezervovat a půjčovat zařízení
INSERT INTO role_permissions (role_id, permission_id) 
VALUES (4, 4), (4, 5), (4, 6);

-- Neregistrovaný uživatel má omezený přístup
-- Například může jen vidět veřejně dostupná zařízení, ale nemá právo je rezervovat ani půjčovat
INSERT INTO role_permissions (role_id, permission_id) 
VALUES (5, 6);

/*Kontrola oprávnění v aplikaci*/
SELECT 1
FROM users u
JOIN role_permissions rp ON u.role_id = rp.role_id
JOIN permissions p ON rp.permission_id = p.permission_id
WHERE u.user_id = ? AND p.permission_name = 'manage_devices';

/**/
