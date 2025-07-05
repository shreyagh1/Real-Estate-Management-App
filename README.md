# Real Estate Portal

A web-based real estate management system for agencies and agents, built with PHP and MySQL.

## Features

- **User Authentication:** Secure login for Admin and Agents.
- **Admin Dashboard:** Manage agents, properties, view reports, and transactions.
- **Agent Dashboard:** Add, edit, and manage properties; view transactions.
- **Property Management:** Add, update, and delete property listings.
- **Transaction Management:** Track sales and rentals.
- **Responsive UI:** Clean, modern interface using Bootstrap 5 and Bootstrap Icons.

## Tech Stack

- **Backend:** PHP (PDO for database access)
- **Frontend:** HTML, CSS (Bootstrap 5), Bootstrap Icons
- **Database:** MySQL

## Setup Instructions

### 1. Clone the Repository
```sh
git clone https://github.com/shreyagh1/real_estate_portal.git
cd real_estate_portal
```

### 2. Database Setup
- Import the provided SQL schema into your MySQL server.
- Update `config/database.php` with your MySQL credentials.

#### SQL Schema
```sql
CREATE TABLE Agents (
    AgentID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    AgencyName VARCHAR(100) NOT NULL,
    Phone VARCHAR(15) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE Sellers (
    SellerID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Phone VARCHAR(15) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE Properties (
    PropertyID INT AUTO_INCREMENT PRIMARY KEY,
    Address VARCHAR(255) NOT NULL,
    City VARCHAR(100) NOT NULL,
    Size_sqft INT NOT NULL,
    Bedrooms INT NOT NULL,
    YearBuilt YEAR NOT NULL,
    Price DECIMAL(12,2) DEFAULT NULL,
    Rent DECIMAL(12,2) DEFAULT NULL,
    SellerID INT NOT NULL,
    CHECK (Price IS NOT NULL OR Rent IS NOT NULL),
    FOREIGN KEY (SellerID) REFERENCES Sellers(SellerID) ON DELETE CASCADE
);

CREATE TABLE Buyers (
    BuyerID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Phone VARCHAR(15) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE Rental (
    RentalID INT AUTO_INCREMENT PRIMARY KEY,
    PropertyID INT NOT NULL,
    BuyerID INT NOT NULL,
    AgentID INT NOT NULL,
    RentStartDate DATE NOT NULL,
    RentEndDate DATE NOT NULL,
    MonthlyRent DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (PropertyID) REFERENCES Properties(PropertyID) ON DELETE CASCADE,
    FOREIGN KEY (BuyerID) REFERENCES Buyers(BuyerID) ON DELETE CASCADE,
    FOREIGN KEY (AgentID) REFERENCES Agents(AgentID) ON DELETE CASCADE
);

CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Role ENUM('Agent', 'Admin') NOT NULL
);

CREATE TABLE Transactions (
    TransactionID INT AUTO_INCREMENT PRIMARY KEY,
    PropertyID INT NOT NULL,
    BuyerID INT NOT NULL,
    AgentID INT NOT NULL,
    TransactionType ENUM('Sale', 'Rent') NOT NULL,
    TransactionDate DATE NOT NULL,
    FinalPrice DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (PropertyID) REFERENCES Properties(PropertyID) ON DELETE CASCADE,
    FOREIGN KEY (BuyerID) REFERENCES Buyers(BuyerID) ON DELETE CASCADE,
    FOREIGN KEY (AgentID) REFERENCES Agents(AgentID) ON DELETE CASCADE
);
```

### 3. Configure Database Connection

Edit `config/database.php` and set your database host, name, username, and password.

```php
private $host = "localhost";
private $db_name = "real_estate";
private $username = "root";
private $password = "your_password";
```

### 4. Run Locally
- Place the project in your web server directory (e.g., `htdocs` for XAMPP).
- Start Apache and MySQL.
- Visit `http://localhost/real_estate_portal` in your browser.

### 5. Default Admin/Agent Setup
- Insert admin and agent users directly into the `Users` table, or use the signup page if enabled.

## Functionalities & How to Use

### User Roles
- **Admin:** Can manage agents, view all properties, transactions, and reports.
- **Agent:** Can add, edit, and manage their own properties, and view their transactions.

### Main Functionalities
- **Login/Signup:**
  - Access the portal via the login page. New users can sign up if enabled.
- **Dashboard:**
  - Admin and agents see a summary of their activities and quick links to main actions.
- **Manage Properties:**
  - Agents can add new properties, edit or delete existing ones.
  - Fill out the property form and submit to add a property.
- **View Transactions:**
  - Agents and admins can view recent sales and rental transactions.
- **Reports (Admin):**
  - Admins can view sales/rental reports and agent performance.
- **Logout:**
  - Securely log out from the portal.

### Navigation
- Use the top navbar to access Dashboard, Manage Properties, View Transactions, and Logout.
- All actions are accessible from the main navigation for ease of use.

## Folder Structure

```
real_estate_portal/
├── admin/           # Admin dashboard and management pages
├── agent/           # Agent dashboard and property management
├── assets/          # CSS and static assets
├── config/          # Database configuration
├── index.php        # Landing page
├── login.php        # Login page
├── logout.php       # Logout script
├── signup.php       # Signup page
└── README.md        # Project documentation
```

## Security Notes

- **Never commit real database credentials to public repositories.** Use environment variables or a local config for production.
- Passwords are securely hashed using PHP’s `password_hash` and `password_verify`.
- All database queries use prepared statements to prevent SQL injection.

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

[MIT](LICENSE) (or your preferred license)

## 👥 Developers

- 👩‍💻 **[Sunidhi Choudhary](https://github.com/sunidhi009)** 
- 👩‍💻 **[Shreya Ghosh](https://github.com/shreyagh1)** 

├── admin/ # Admin dashboard and management pages.
├── agent/ # Agent dashboard and property management.
├── assets/ # CSS and static assets.
├── config/ # Database configuration.
├── index.php # Landing page.
├── login.php # Login page.
├── logout.php # Logout script.
├── signup.php # Signup page.


## Security Notes

- **Never commit real database credentials to public repositories.** Use environment variables or a local config for production.
- Passwords are securely hashed using PHP’s `password_hash` and `password_verify`.
- All database queries use prepared statements to prevent SQL injection.

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

[MIT](LICENSE) 

---

**Developed by [Shreya Ghosh](https://github.com/shreyagh1)**
