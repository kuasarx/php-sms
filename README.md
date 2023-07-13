# SMS Library

This is a PHP library for interacting with SMS functionality using [Gammu](https://github.com/gammu/gammu/tree/master).

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Features](#features)
- [Contributing](#contributing)

## Installation

### Requirements

- Runs on PHP 5.6 or above
- Recomended PHP 7 or above
- [Gammu](https://github.com/gammu/gammu/tree/master) installed and configured

1. Clone the repository:

   ```bash
   git clone https://github.com/your-username/sms-library.git
   ```
1. Install the library dependencies using Composer:
   ```bash
   composer install
   ```
## Usage
### Initialize the SMS object
```php
use SMS\SMS;

$sms = new SMS('/path/to/gammu', '/path/to/config', 'section_name');
```
### Send an SMS
```php
$number = '+1234567890';
$message = 'Hello, World!';

$response = '';
$sms->send($number, $message, $response);

if ($response) {
    echo "SMS sent successfully!\n";
} else {
    echo "Failed to send SMS.\n";
}
```
### Delete an SMS
```php
$folder = 'inbox';
$start = 1;
$stop = 10;

$response = '';
$sms->delete($folder, $start, $stop, $response);

if ($response) {
    echo "SMS deleted successfully!\n";
} else {
    echo "Failed to delete SMS.\n";
}
```
### Get SMS Messages
```php
$messages = $sms->getMessages();

// Process and display the messages
foreach ($messages as $folder => $messageList) {
    foreach ($messageList as $messageId => $message) {
        // Process individual message data
    }
}
```
### Get Phonebook Contacts
```php
$contacts = $sms->getPhoneBook();

// Process and display the contacts
foreach ($contacts as $index => $contact) {
    // Process individual contact data
}
```
## Features
- Send SMS messages
- Delete SMS messages
- Retrieve SMS messages
- Access phonebook contacts

## Contributing
Contributions are welcome! Here's how you can contribute:

1. Fork the repository.
2. Create a new branch.
3. Make your changes.
4. Test your changes.
5. Submit a pull request.

