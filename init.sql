CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('administrator', 'atelier_manager', 'teacher', 'registered_user', 'unregistered_user') NOT NULL,
    atelier_id INT NULL,
    email VARCHAR(255) NOT NULL,
    profile_info TEXT
);

CREATE TABLE ateliers (
    atelier_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT,
    FOREIGN KEY (manager_id) REFERENCES users(user_id)
);

ALTER TABLE users 
ADD CONSTRAINT fk_atelier
FOREIGN KEY (atelier_id) 
REFERENCES ateliers(atelier_id);

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

/*Vytvoření uživatelských účtů*/
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL
);

-- Definice zahraničního klíče mezi uživateli a rolemi
ALTER TABLE users 
ADD CONSTRAINT fk_role
FOREIGN KEY (role_id) 
REFERENCES roles(role_id);

/*Definování oprávnění (pouze ukázka konceptu)*/
CREATE TABLE permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL
);

/*Přiřazení oprávnění k rolím*/
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
);
