
# Number Games

Number Games API is a PHP-based system designed to manage the logic and data handling for an interactive number guessing game. This backend is responsible for facilitating communication between the game client and the server, processing user inputs, generating game challenges, maintaining game state, and managing user transactions.

## Technologies Used:

### PHP: 
The backend logic is implemented using PHP, a server-side scripting language known for its versatility and compatibility with web servers.
### MySQL: 
A relational database management system (RDBMS) like MySQL is utilized for storing and managing game data, including user profiles, scores, and game challenges.
### RESTful API: 
The backend follows REST principles to design its API, providing a standardized and intuitive interface for communication between the client and server.
Conclusion:
### Docker
Docker is used for containerization to package the application and its dependencies. This ensures the application runs the same way in local and production environment.
### PHPUnit
This API uses PHPUnit for unit testing of individual components. It ensures each part of the application works correctly as intended.
### Phinx
Although this is a native PHP, we utilize the phinx library to automate the migration of schema from dev environment to the database.

