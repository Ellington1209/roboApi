# Projeto Laravel com Docker e Autenticação

Projeto Laravel 12 com Docker, PostgreSQL e autenticação via Laravel Sanctum.

## Estrutura do Projeto

- **Laravel 12**: Framework PHP
- **Docker**: Containerização com PHP 8.3-FPM, Nginx, PostgreSQL 16 e pgAdmin
- **Laravel Sanctum**: Autenticação via tokens API
- **PostgreSQL**: Banco de dados

## Requisitos

- Docker
- Docker Compose

## Instalação

1. Clone o repositório e entre no diretório:
```bash
cd api
```

2. Inicie os containers:
```bash
docker compose up -d
```

3. Entre no container do app:
```bash
docker-compose exec app bash
```

4. Instale as dependências do Composer:
```bash
composer install
```

5. Gere a chave da aplicação:
```bash
php artisan key:generate
```

6. Execute as migrations:
```bash
php artisan migrate
```

7. (Opcional) Execute o seeder para criar usuários de teste:
```bash
php artisan db:seed
```

## Usuários de Teste

Após executar o seeder, você terá os seguintes usuários:

- **Admin**: `admin@example.com` / `password`
- **Test User**: `test@example.com` / `password`

## Endpoints da API

### Autenticação

#### Login
```http
POST http://localhost:8081/api/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password"
}
```

**Resposta:**
```json
{
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com"
  },
  "token": "1|..."
}
```

#### Me (usuário autenticado)
```http
GET http://localhost:8081/api/auth/me
Authorization: Bearer {token}
```

#### Logout
```http
POST http://localhost:8081/api/auth/logout
Authorization: Bearer {token}
```

## Serviços Docker

- **App**: `http://localhost:8081` (Laravel via Nginx)
- **PostgreSQL**: `localhost:5433`
- **pgAdmin**: `http://localhost:5051`
  - Email: `admin@admin.com`
  - Senha: `admin`

## Estrutura de Pastas

```
api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── modules/
│   │           └── Auth/
│   │               └── AuthController.php
│   ├── Models/
│   │   └── User.php
│   └── Services/
│       └── AuthService.php
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── docker/
│   └── nginx/
│       └── default.conf
├── routes/
│   └── api.php
├── docker-compose.yml
└── Dockerfile
```

## Comandos Úteis

### Docker
```bash
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose down

# Ver logs
docker-compose logs -f

# Entrar no container
docker-compose exec app bash
```

### Laravel
```bash
# Executar migrations
php artisan migrate

# Executar seeders
php artisan db:seed

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Observações

- O projeto usa Laravel 12 com estrutura de pastas modular
- Autenticação via Laravel Sanctum (tokens API)
- Banco de dados PostgreSQL 16
- Validação de dados no controller
- Respostas JSON padronizadas
- Mensagens de erro em português

