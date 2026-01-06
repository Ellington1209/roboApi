# Documentação da API - Sistema de Controle de Robôs

## Instalação e Configuração

### Passos Iniciais

1. **Executar as migrations:**
```bash
docker compose exec app php artisan migrate
```

2. **Criar o link simbólico do storage (IMPORTANTE para imagens):**
```bash
docker compose exec app php artisan storage:link
```

Este comando cria o link simbólico `public/storage` → `storage/app/public`, permitindo que as imagens dos robôs sejam acessíveis via URL pública.

**⚠️ Sem este comando, as imagens retornarão erro 404!**

3. **Criar usuários de teste (opcional):**
```bash
docker compose exec app php artisan db:seed
```

---

## Base URL
```
http://localhost:8081/api
```

## Autenticação

Todas as rotas de robôs requerem autenticação via Bearer Token (Laravel Sanctum).

**Header obrigatório:**
```
Authorization: Bearer {token}
```

---

## Endpoints de Robôs

### 1. Listar Robôs

**GET** `/robots`

Lista todos os robôs do usuário autenticado. Super admins podem ver todos os robôs.

**Query Parameters (opcionais):**
- `language` (string): Filtrar por linguagem (pascal, python, js, other)
- `is_active` (boolean): Filtrar por status ativo/inativo
- `search` (string): Buscar por nome ou descrição
- `per_page` (integer): Itens por página (padrão: 15)
- `page` (integer): Número da página

**Exemplo de requisição:**
```http
GET /api/robots?language=python&is_active=true&search=trader&per_page=20
Authorization: Bearer 1|abc123...
```

**Resposta de sucesso (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Robô Trader Python",
      "description": "Robô para trading automatizado",
      "language": "python",
      "tags": ["trading", "forex", "automation"],
      "code": "def trade():\n    # código do robô...",
      "is_active": true,
      "version": 1,
      "last_executed_at": null,
      "created_at": "2026-01-05T20:00:00.000000Z",
      "updated_at": "2026-01-05T20:00:00.000000Z",
      "deleted_at": null,
      "user": {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com"
      },
      "parameters": [
        {
          "id": 1,
          "robot_id": 1,
          "key": "ptsGain",
          "label": "Pontos Gain",
          "type": "number",
          "value": 50,
          "default_value": 50,
          "required": true,
          "options": null,
          "validation_rules": {
            "min": 1,
            "max": 1000
          },
          "group": "config",
          "sort_order": 0,
          "created_at": "2026-01-05T20:00:00.000000Z",
          "updated_at": "2026-01-05T20:00:00.000000Z"
        }
      ],
      "images": [
        {
          "id": 1,
          "robot_id": 1,
          "title": "Configuração Principal",
          "caption": "Tela de configuração do robô",
          "disk": "public",
          "path": "robots/1/image1.png",
          "url": "/storage/robots/1/image1.png",
          "thumbnail_path": null,
          "mime_type": "image/png",
          "size_bytes": 245678,
          "width": 1920,
          "height": 1080,
          "is_primary": true,
          "sort_order": 0,
          "created_at": "2026-01-05T20:00:00.000000Z",
          "updated_at": "2026-01-05T20:00:00.000000Z"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 2. Criar Robô

**POST** `/robots`

Cria um novo robô com código, parâmetros e imagens.

**⚠️ IMPORTANTE: Parâmetros são totalmente dinâmicos!**
- Cada robô pode ter **quantos parâmetros quiser** (0, 1, 5, 10, 20...)
- Cada parâmetro tem seu **próprio nome único** (`key`)
- Não há limite de quantidade ou nomes pré-definidos
- Cada robô define seus próprios parâmetros conforme necessário

**Content-Type:** `multipart/form-data` (para upload de imagens) ou `application/json` (sem imagens)

**Payload:**
```json
{
  "name": "Robô Trader Python",
  "description": "Robô para trading automatizado no mercado forex",
  "language": "python",
  "tags": ["trading", "forex", "automation"],
  "code": "def trade():\n    # código do robô aqui\n    pass",
  "is_active": true,
  "parameters": [
    {
      "key": "ptsGain",
      "label": "Pontos Gain",
      "type": "number",
      "value": 50,
      "default_value": 50,
      "required": true,
      "options": null,
      "validation_rules": {
        "min": 1,
        "max": 1000
      },
      "group": "config",
      "sort_order": 0
    },
    {
      "key": "ptsLoss",
      "label": "Pontos Loss",
      "type": "number",
      "value": 30,
      "default_value": 30,
      "required": true,
      "options": null,
      "validation_rules": {
        "min": 1,
        "max": 500
      },
      "group": "config",
      "sort_order": 1
    },
    {
      "key": "symbol",
      "label": "Símbolo",
      "type": "select",
      "value": "EURUSD",
      "default_value": "EURUSD",
      "required": true,
      "options": ["EURUSD", "GBPUSD", "USDJPY", "AUDUSD"],
      "validation_rules": null,
      "group": "config",
      "sort_order": 2
    }
  ]
}
```

**Exemplos de robôs com diferentes quantidades de parâmetros:**

**Robô simples (1 parâmetro):**
```json
{
  "name": "Robô Simples",
  "language": "python",
  "code": "print('Hello')",
  "parameters": [
    {
      "key": "intervalo",
      "label": "Intervalo (segundos)",
      "type": "number",
      "value": 60,
      "default_value": 60,
      "required": true,
      "sort_order": 0
    }
  ]
}
```

**Robô complexo (10 parâmetros):**
```json
{
  "name": "Robô Avançado",
  "language": "python",
  "code": "# código complexo...",
  "parameters": [
    {
      "key": "stopLoss",
      "label": "Stop Loss",
      "type": "number",
      "value": 50,
      "sort_order": 0
    },
    {
      "key": "takeProfit",
      "label": "Take Profit",
      "type": "number",
      "value": 100,
      "sort_order": 1
    },
    {
      "key": "maxTrades",
      "label": "Máximo de Trades",
      "type": "number",
      "value": 5,
      "sort_order": 2
    },
    {
      "key": "timeframe",
      "label": "Timeframe",
      "type": "select",
      "value": "M15",
      "options": ["M1", "M5", "M15", "H1", "H4"],
      "sort_order": 3
    },
    {
      "key": "enableNotifications",
      "label": "Ativar Notificações",
      "type": "boolean",
      "value": true,
      "sort_order": 4
    },
    {
      "key": "riskPercent",
      "label": "Risco (%)",
      "type": "number",
      "value": 2,
      "sort_order": 5
    },
    {
      "key": "symbol",
      "label": "Par de Moedas",
      "type": "string",
      "value": "EURUSD",
      "sort_order": 6
    },
    {
      "key": "magicNumber",
      "label": "Magic Number",
      "type": "number",
      "value": 12345,
      "sort_order": 7
    },
    {
      "key": "slippage",
      "label": "Slippage",
      "type": "number",
      "value": 3,
      "sort_order": 8
    },
    {
      "key": "comment",
      "label": "Comentário",
      "type": "string",
      "value": "Robô automático",
      "sort_order": 9
    }
  ]
}
```

**Robô sem parâmetros (array vazio ou omitir):**
```json
{
  "name": "Robô Sem Parâmetros",
  "language": "python",
  "code": "print('Sem configurações')",
  "parameters": []
}
```

**Para upload de imagens (multipart/form-data):**
- ✅ **Múltiplas imagens permitidas!** Você pode enviar quantas imagens quiser
- `images[]`: Array de arquivos de imagem (jpeg, png, jpg, gif, webp, máximo 10MB cada)
  - Exemplo: `images[0]`, `images[1]`, `images[2]`, etc.
  - Ou simplesmente: `images[]` múltiplas vezes
- `image_titles[]`: Array opcional de títulos para as imagens (mesma ordem)
- `image_captions[]`: Array opcional de legendas para as imagens (mesma ordem)
- A primeira imagem enviada será marcada como `is_primary: true` automaticamente

**Exemplo com cURL (com múltiplas imagens):**
```bash
# Enviando 3 imagens de uma vez
curl -X POST http://localhost:8081/api/robots \
  -H "Authorization: Bearer {token}" \
  -F "name=Robô Trader Python" \
  -F "description=Robô para trading automatizado" \
  -F "language=python" \
  -F "tags[]=trading" \
  -F "tags[]=forex" \
  -F "code=def trade(): pass" \
  -F "is_active=true" \
  -F "parameters[0][key]=ptsGain" \
  -F "parameters[0][label]=Pontos Gain" \
  -F "parameters[0][type]=number" \
  -F "parameters[0][value]=50" \
  -F "parameters[0][default_value]=50" \
  -F "parameters[0][required]=true" \
  -F "parameters[0][group]=config" \
  -F "parameters[0][sort_order]=0" \
  -F "images[]=@/path/to/image1.png" \
  -F "images[]=@/path/to/image2.png" \
  -F "images[]=@/path/to/image3.png" \
  -F "image_titles[0]=Configuração Principal" \
  -F "image_titles[1]=Tela de Resultados" \
  -F "image_titles[2]=Gráfico de Performance" \
  -F "image_captions[0]=Tela de configuração do robô" \
  -F "image_captions[1]=Resultados das operações" \
  -F "image_captions[2]=Gráfico mostrando performance"
```

**💡 Dica:** Você pode enviar quantas imagens quiser! Basta adicionar mais `images[]` no form-data. Não há limite de quantidade!

**Resposta de sucesso (201):**
```json
{
  "message": "Robô criado com sucesso",
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Robô Trader Python",
    "description": "Robô para trading automatizado no mercado forex",
    "language": "python",
    "tags": ["trading", "forex", "automation"],
    "code": "def trade():\n    # código do robô aqui\n    pass",
    "is_active": true,
    "version": 1,
    "last_executed_at": null,
    "created_at": "2026-01-05T20:00:00.000000Z",
    "updated_at": "2026-01-05T20:00:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com"
    },
    "parameters": [
      {
        "id": 1,
        "robot_id": 1,
        "key": "ptsGain",
        "label": "Pontos Gain",
        "type": "number",
        "value": 50,
        "default_value": 50,
        "required": true,
        "options": null,
        "validation_rules": {
          "min": 1,
          "max": 1000
        },
        "group": "config",
        "sort_order": 0,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T20:00:00.000000Z"
      }
    ],
    "images": [
      {
        "id": 1,
        "robot_id": 1,
        "title": "Configuração Principal",
        "caption": "Tela de configuração do robô",
        "disk": "public",
        "path": "robots/1/image1.png",
        "url": "/storage/robots/1/image1.png",
        "thumbnail_path": null,
        "mime_type": "image/png",
        "size_bytes": 245678,
        "width": 1920,
        "height": 1080,
        "is_primary": true,
        "sort_order": 0,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T20:00:00.000000Z"
      }
    ]
  }
}
```

**Resposta de erro de validação (422):**
```json
{
  "message": "Erro de validação",
  "errors": {
    "name": ["O campo nome é obrigatório."],
    "language": ["O campo language deve ser um dos seguintes: pascal, python, js, other."]
  }
}
```

---

### 3. Visualizar Robô

**GET** `/robots/{id}`

Retorna os detalhes completos de um robô específico, incluindo versões.

**Exemplo de requisição:**
```http
GET /api/robots/1
Authorization: Bearer 1|abc123...
```

**Resposta de sucesso (200):**
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Robô Trader Python",
    "description": "Robô para trading automatizado",
    "language": "python",
    "tags": ["trading", "forex", "automation"],
    "code": "def trade():\n    # código do robô...",
    "is_active": true,
    "version": 2,
    "last_executed_at": null,
    "created_at": "2026-01-05T20:00:00.000000Z",
    "updated_at": "2026-01-05T21:00:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com"
    },
    "parameters": [
      {
        "id": 1,
        "robot_id": 1,
        "key": "ptsGain",
        "label": "Pontos Gain",
        "type": "number",
        "value": 50,
        "default_value": 50,
        "required": true,
        "options": null,
        "validation_rules": {
          "min": 1,
          "max": 1000
        },
        "group": "config",
        "sort_order": 0,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T20:00:00.000000Z"
      }
    ],
    "images": [
      {
        "id": 1,
        "robot_id": 1,
        "title": "Configuração Principal",
        "caption": "Tela de configuração do robô",
        "disk": "public",
        "path": "robots/1/image1.png",
        "url": "/storage/robots/1/image1.png",
        "thumbnail_path": null,
        "mime_type": "image/png",
        "size_bytes": 245678,
        "width": 1920,
        "height": 1080,
        "is_primary": true,
        "sort_order": 0,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T20:00:00.000000Z"
      }
    ],
    "versions": [
      {
        "id": 2,
        "robot_id": 1,
        "version": 2,
        "code": "def trade():\n    # código atualizado...",
        "changelog": "Correção de bug na lógica de stop loss",
        "is_current": true,
        "created_by": 1,
        "created_at": "2026-01-05T21:00:00.000000Z",
        "updated_at": "2026-01-05T21:00:00.000000Z",
        "creator": {
          "id": 1,
          "name": "Admin",
          "email": "admin@example.com"
        }
      },
      {
        "id": 1,
        "robot_id": 1,
        "version": 1,
        "code": "def trade():\n    # código inicial...",
        "changelog": "Versão inicial",
        "is_current": false,
        "created_by": 1,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T20:00:00.000000Z"
      }
    ]
  }
}
```

**Resposta de erro (404):**
```json
{
  "message": "No query results for model [App\\Models\\Robot] 1"
}
```

---

### 4. Atualizar Robô

**PUT/PATCH** `/robots/{id}`

Atualiza um robô existente. Permite atualizar código, parâmetros, adicionar/remover imagens e criar nova versão.

**Content-Type:** `multipart/form-data` (se houver imagens) ou `application/json`

**Payload (campos opcionais - apenas os que deseja atualizar):**
```json
{
  "name": "Robô Trader Python v2",
  "description": "Versão atualizada do robô",
  "language": "python",
  "tags": ["trading", "forex", "automation", "updated"],
  "code": "def trade():\n    # código atualizado...",
  "is_active": true,
  "parameters": [
    {
      "id": 1,
      "key": "ptsGain",
      "label": "Pontos Gain",
      "type": "number",
      "value": 60,
      "default_value": 50,
      "required": true,
      "options": null,
      "validation_rules": {
        "min": 1,
        "max": 1000
      },
      "group": "config",
      "sort_order": 0
    },
    {
      "key": "newParam",
      "label": "Novo Parâmetro",
      "type": "string",
      "value": "valor",
      "default_value": "valor",
      "required": false,
      "options": null,
      "validation_rules": null,
      "group": "advanced",
      "sort_order": 10
    }
  ],
  "images": [],
  "image_titles": [],
  "image_captions": [],
  "delete_image_ids": [2, 3],
  "create_version": true,
  "changelog": "Correção de bug na lógica de stop loss"
}
```

**Observações:**
- `parameters`: Array completo de parâmetros. Parâmetros com `id` serão atualizados, sem `id` serão criados, e os que não estiverem no array serão deletados.
- `images`: Array de novas imagens para upload
- `delete_image_ids`: Array de IDs de imagens para deletar
- `create_version`: Se `true`, cria uma nova versão quando o código é alterado
- `changelog`: Descrição da mudança (usado ao criar versão)

**Exemplo de requisição:**
```http
PUT /api/robots/1
Authorization: Bearer 1|abc123...
Content-Type: application/json

{
  "name": "Robô Trader Python v2",
  "code": "def trade():\n    # código atualizado...",
  "create_version": true,
  "changelog": "Correção de bug"
}
```

**Resposta de sucesso (200):**
```json
{
  "message": "Robô atualizado com sucesso",
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Robô Trader Python v2",
    "description": "Versão atualizada do robô",
    "language": "python",
    "tags": ["trading", "forex", "automation", "updated"],
    "code": "def trade():\n    # código atualizado...",
    "is_active": true,
    "version": 2,
    "last_executed_at": null,
    "created_at": "2026-01-05T20:00:00.000000Z",
    "updated_at": "2026-01-05T21:00:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com"
    },
    "parameters": [
      {
        "id": 1,
        "robot_id": 1,
        "key": "ptsGain",
        "label": "Pontos Gain",
        "type": "number",
        "value": 60,
        "default_value": 50,
        "required": true,
        "options": null,
        "validation_rules": {
          "min": 1,
          "max": 1000
        },
        "group": "config",
        "sort_order": 0,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T21:00:00.000000Z"
      }
    ],
    "images": [
      {
        "id": 1,
        "robot_id": 1,
        "title": "Configuração Principal",
        "caption": "Tela de configuração do robô",
        "disk": "public",
        "path": "robots/1/image1.png",
        "url": "/storage/robots/1/image1.png",
        "thumbnail_path": null,
        "mime_type": "image/png",
        "size_bytes": 245678,
        "width": 1920,
        "height": 1080,
        "is_primary": true,
        "sort_order": 0,
        "created_at": "2026-01-05T20:00:00.000000Z",
        "updated_at": "2026-01-05T20:00:00.000000Z"
      }
    ]
  }
}
```

---

### 5. Deletar Robô

**DELETE** `/robots/{id}`

Remove um robô (soft delete). Super admins podem deletar qualquer robô, usuários comuns apenas os próprios.

**Exemplo de requisição:**
```http
DELETE /api/robots/1
Authorization: Bearer 1|abc123...
```

**Resposta de sucesso (200):**
```json
{
  "message": "Robô deletado com sucesso"
}
```

**Resposta de erro (404):**
```json
{
  "message": "No query results for model [App\\Models\\Robot] 1"
}
```

---

## Regras de Acesso

### Usuários Comuns
- Podem ver apenas seus próprios robôs
- Podem criar, editar e deletar apenas seus próprios robôs

### Super Admins
- Podem ver todos os robôs (filtro por `user_id` é ignorado)
- Podem editar e deletar qualquer robô

---

## Tipos de Dados

### Linguagens Suportadas
- `pascal`
- `python`
- `js`
- `other`

### Tipos de Parâmetros
- `number`: Valor numérico
- `string`: Texto
- `boolean`: Verdadeiro/Falso
- `select`: Lista de opções (definida em `options`)

### Estrutura de Parâmetro
```json
{
  "key": "nomeUnicoDoParametro",
  "label": "Nome Exibido",
  "type": "number|string|boolean|select",
  "value": "valor atual",
  "default_value": "valor padrão (opcional)",
  "required": true|false,
  "options": ["opcao1", "opcao2"] | null, // apenas para type=select
  "validation_rules": {
    "min": 1,
    "max": 1000,
    "regex": "^[A-Z]+$"
  } | null,
  "group": "nomeDoGrupo" | null,
  "sort_order": 0
}
```

---

## 🔑 Parâmetros Dinâmicos - Como Funciona

### Conceito Principal

**Os parâmetros são TOTALMENTE DINÂMICOS e FLEXÍVEIS!**

Cada robô define seus próprios parâmetros conforme sua necessidade. Não há estrutura fixa ou parâmetros obrigatórios.

### Exemplos Práticos

#### Exemplo 1: Robô Simples (1 parâmetro)
```json
{
  "name": "Robô Timer",
  "language": "python",
  "code": "# código...",
  "parameters": [
    {
      "key": "intervalo",
      "label": "Intervalo em segundos",
      "type": "number",
      "value": 60
    }
  ]
}
```

#### Exemplo 2: Robô Médio (3 parâmetros)
```json
{
  "name": "Robô Trader Básico",
  "language": "python",
  "code": "# código...",
  "parameters": [
    {
      "key": "ptsGain",
      "label": "Pontos Gain",
      "type": "number",
      "value": 50
    },
    {
      "key": "ptsLoss",
      "label": "Pontos Loss",
      "type": "number",
      "value": 30
    },
    {
      "key": "symbol",
      "label": "Par de Moedas",
      "type": "select",
      "value": "EURUSD",
      "options": ["EURUSD", "GBPUSD", "USDJPY"]
    }
  ]
}
```

#### Exemplo 3: Robô Complexo (10 parâmetros)
```json
{
  "name": "Robô Trader Avançado",
  "language": "python",
  "code": "# código complexo...",
  "parameters": [
    { "key": "stopLoss", "label": "Stop Loss", "type": "number", "value": 50 },
    { "key": "takeProfit", "label": "Take Profit", "type": "number", "value": 100 },
    { "key": "maxTrades", "label": "Máximo de Trades", "type": "number", "value": 5 },
    { "key": "timeframe", "label": "Timeframe", "type": "select", "value": "M15", "options": ["M1", "M5", "M15", "H1"] },
    { "key": "enableNotifications", "label": "Ativar Notificações", "type": "boolean", "value": true },
    { "key": "riskPercent", "label": "Risco (%)", "type": "number", "value": 2 },
    { "key": "symbol", "label": "Par de Moedas", "type": "string", "value": "EURUSD" },
    { "key": "magicNumber", "label": "Magic Number", "type": "number", "value": 12345 },
    { "key": "slippage", "label": "Slippage", "type": "number", "value": 3 },
    { "key": "comment", "label": "Comentário", "type": "string", "value": "Robô automático" }
  ]
}
```

#### Exemplo 4: Robô Sem Parâmetros
```json
{
  "name": "Robô Autônomo",
  "language": "python",
  "code": "# código sem configurações...",
  "parameters": []
}
```

### Características Importantes

1. **Quantidade Variável:**
   - Robô A: 1 parâmetro
   - Robô B: 5 parâmetros
   - Robô C: 20 parâmetros
   - ✅ Tudo é permitido!

2. **Nomes Personalizados:**
   - Cada robô escolhe os nomes dos seus parâmetros
   - `key` é único apenas dentro do mesmo robô
   - Diferentes robôs podem ter parâmetros com o mesmo nome

3. **Tipos Misturados:**
   - Um robô pode ter parâmetros `number`, `string`, `boolean` e `select` misturados
   - Não há restrição de tipos por robô

4. **Flexibilidade Total:**
   - Adicione parâmetros quando quiser
   - Remova parâmetros quando quiser
   - Modifique parâmetros quando quiser
   - Cada robô é independente!

---

## Códigos de Status HTTP

- `200` - Sucesso
- `201` - Criado com sucesso
- `401` - Não autenticado
- `403` - Sem permissão
- `404` - Não encontrado
- `422` - Erro de validação
- `500` - Erro interno do servidor

---

## Exemplos de Uso

### Criar robô completo com Postman/Insomnia

1. **Método:** POST
2. **URL:** `http://localhost:8081/api/robots`
3. **Headers:**
   - `Authorization: Bearer {seu_token}`
   - `Accept: application/json`
4. **Body:** Form-data
   - Adicione os campos do robô
   - Adicione arquivos em `images[]`
   - Adicione parâmetros como JSON string ou campos individuais

### Atualizar código e criar versão

```json
{
  "code": "def trade():\n    # novo código...",
  "create_version": true,
  "changelog": "Implementação de nova estratégia"
}
```

### Adicionar novas imagens

Envie `images[]` como array de arquivos no form-data junto com os outros campos. Você pode enviar quantas imagens quiser!

**Exemplo com múltiplas imagens:**
```bash
# No Postman/Insomnia, adicione múltiplos arquivos no campo "images[]"
# Ou via cURL:
curl -X PUT http://localhost:8081/api/robots/1 \
  -H "Authorization: Bearer {token}" \
  -F "images[]=@/path/to/image1.png" \
  -F "images[]=@/path/to/image2.png" \
  -F "images[]=@/path/to/image3.png" \
  -F "images[]=@/path/to/image4.png"
```

### Remover imagens específicas

```json
{
  "delete_image_ids": [2, 3, 5]
}
```

Isso remove apenas as imagens com IDs 2, 3 e 5, mantendo as outras.

---

## Notas Importantes

1. **Parâmetros Dinâmicos (MUITO IMPORTANTE):**
   - ✅ **Cada robô pode ter quantos parâmetros quiser** (0, 1, 5, 10, 20, 100...)
   - ✅ **Cada parâmetro tem seu próprio nome único** (`key`) - você escolhe o nome
   - ✅ **Não há parâmetros pré-definidos** - cada robô define os seus
   - ✅ **Cada robô pode ter parâmetros completamente diferentes** do outro
   - ✅ **Exemplo:** Robô A pode ter `ptsGain` e `ptsLoss`, enquanto Robô B pode ter `stopLoss`, `takeProfit`, `maxTrades`, `timeframe`, etc.
   - ✅ **O campo `key` é único apenas dentro do mesmo robô** (pode repetir entre robôs diferentes)
   - ✅ **Você define os nomes dos parâmetros** conforme a necessidade de cada robô

2. **Upload de Imagens (Múltiplas):**
   - ✅ **Cada robô pode ter VÁRIAS imagens!** Não há limite de quantidade
   - Formato: `multipart/form-data`
   - Tipos aceitos: jpeg, png, jpg, gif, webp
   - Tamanho máximo: 10MB por imagem
   - As imagens são salvas em `storage/app/public/robots/{robot_id}/`
   - **IMPORTANTE:** Certifique-se de executar `php artisan storage:link` antes de fazer upload de imagens
   - URLs das imagens são retornadas completas (ex: `http://localhost:8081/storage/robots/1/image.webp`)
   - A primeira imagem enviada é marcada como `is_primary: true`
   - Imagens são ordenadas por `sort_order` (baseado na ordem de envio)
   - Você pode adicionar mais imagens depois via UPDATE
   - Você pode deletar imagens específicas via UPDATE usando `delete_image_ids`

3. **Versionamento:**
   - Ao atualizar o código com `create_version: true`, uma nova versão é criada
   - A versão anterior é marcada como `is_current: false`
   - O campo `version` do robô é incrementado automaticamente

4. **Gerenciamento de Parâmetros:**
   - Ao atualizar, envie o array completo de parâmetros
   - Parâmetros não enviados serão deletados
   - Use `id` para atualizar existentes, omita para criar novos
   - Você pode adicionar, remover ou modificar parâmetros a qualquer momento

5. **Soft Delete:**
   - Robôs deletados não são removidos permanentemente
   - Use `withTrashed()` para acessar robôs deletados (se necessário)

6. **Filtros na Listagem:**
   - Super admins veem todos os robôs
   - Usuários comuns veem apenas os próprios
   - Filtros adicionais podem ser aplicados via query parameters

