# Instruções de Setup

## Passo a Passo para Iniciar o Projeto

### 1. Iniciar os Containers Docker

```bash
docker compose up -d
```

### 2. Entrar no Container do App

```bash
docker compose exec app bash
```

### 3. Instalar Dependências do Composer

```bash
composer install
```

### 4. Gerar Chave da Aplicação

```bash
php artisan key:generate
```

### 5. Executar Migrations

```bash
php artisan migrate
```

### 6. (Opcional) Criar Usuários de Teste

```bash
php artisan db:seed
```

## Configuração do .env

O arquivo `.env` já está configurado com as seguintes variáveis importantes:

- `DB_CONNECTION=pgsql`
- `DB_HOST=postgres`
- `DB_PORT=5432`
- `DB_DATABASE=saas`
- `DB_USERNAME=user`
- `DB_PASSWORD=password`
- `SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1`

**Importante**: Após executar `php artisan key:generate`, a variável `APP_KEY` será preenchida automaticamente.

## Testando a API

### Login

```bash
curl -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

### Me (usuário autenticado)

```bash
curl -X GET http://localhost:8081/api/auth/me \
  -H "Authorization: Bearer {seu_token_aqui}"
```

### Logout

```bash
curl -X POST http://localhost:8081/api/auth/logout \
  -H "Authorization: Bearer {seu_token_aqui}"
```

## Acessos

- **API**: http://localhost:8081
- **pgAdmin**: http://localhost:5051
  - Email: `admin@admin.com`
  - Senha: `admin`
- **PostgreSQL**: localhost:5433

## Troubleshooting

### Erro de permissões

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Reconstruir containers

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Ver logs

```bash
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
```

