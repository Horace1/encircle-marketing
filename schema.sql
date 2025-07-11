USE encircle_marketing;

CREATE TABLE tyres (
       id INT AUTO_INCREMENT PRIMARY KEY,
       website VARCHAR(20),
       brand VARCHAR(20),
       pattern VARCHAR(100),
       size VARCHAR(50),
       price INT UNSIGNED,
       rating DECIMAL(3,1),
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);