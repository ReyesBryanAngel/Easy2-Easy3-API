
# GTSI - BE - Color Game

The Color Game Backend is a PHP-based system designed to manage the logic and data handling for an interactive color guessing game. This backend is responsible for facilitating communication between the game client and the server, processing user inputs, generating game challenges, maintaining game state, and managing user scores.



## Technologies Used:

### PHP: 
The backend logic is implemented using PHP, a server-side scripting language known for its versatility and compatibility with web servers.
### MySQL: 
A relational database management system (RDBMS) like MySQL is utilized for storing and managing game data, including user profiles, scores, and game challenges.
### RESTful API: 
The backend follows REST principles to design its API, providing a standardized and intuitive interface for communication between the client and server.
Conclusion:

The Color Game Backend implemented in PHP provides a robust infrastructure for creating an engaging and interactive color guessing game. By handling user authentication, game logic, scoring, and data management, it ensures a seamless gaming experience while prioritizing security and scalability.




## Contributing

Contributions are always welcome!

See `contributing.md` for ways to get started.

Please adhere to this project's `code of conduct`.


## Authors

- [@austineraye](https://www.github.com/austineraye)


## API Reference

#### Get all items

```http
  GET /api/items
```

| Parameter | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `game_api_key` | `string` | **Required**. Your API key |

#### Get item

```http
  GET /api/items/${id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `string` | **Required**. Id of item to fetch |

#### add(num1, num2)

Takes two numbers and returns the sum.

