# ELOMOB

[![CI](https://github.com/erlonmt/elomob/actions/workflows/ci.yml/badge.svg)](https://github.com/erlonmt/elomob/actions/workflows/ci.yml)

Aplicação acadêmica de mobilidade no Recife para a AV2 da UNINASSAU. Ela implementa a jornada do protótipo da AV1 (início, busca de rota, resultados, detalhes do trajeto e mapa) e completa a aplicação com horários, pontos de integração, rotas salvas, perfil, entrada na conta e sobre, em 11 telas. Usa React e Next.js, API REST Laravel, PostgreSQL com PostGIS e Redis. A interface adapta a navegação e a distribuição do conteúdo para celular, tablet e desktop.

## Equipe

Equipe Elomob, turma ADS 4MB, disciplina Tópicos Integradores, com o professor Breno Holanda. Desafio do Banco de Oportunidades: falta de integração entre os modais de transporte.

- Cauã Vitor Oliveira
- Erlon Matheus de Andrade Oliveira
- Guilherme Silva Marinho
- João Vitor de Santana Pereira
- Julio Vinicius de Almeida Uchoa Correia

## Executar com Docker

Clone o repositório e suba os serviços:

```bash
git clone https://github.com/erlonmt/elomob.git
cd elomob
docker compose up --build
```

Abra http://localhost:8000. A primeira execução constrói o frontend e a API, aplica as migrações e insere os dados de demonstração. Não é necessário instalar PHP, Composer ou Node no computador para esse caminho. As senhas de banco no Compose pertencem ao ambiente local de desenvolvimento.

O nginx é a entrada: recebe as requisições do navegador na porta 8000, passa ao Next.js e informa o IP de cada cliente, que a API usa nos limites de requisições. O Next.js encaminha `/api/v1` ao Laravel na mesma origem, preservando cookies de sessão e proteção CSRF. O Laravel roda no PHP-FPM, em modo de produção, sem os pacotes de desenvolvimento, e o nginx também o expõe diretamente em http://localhost:8002/api/v1; os endpoints estão em [docs/API.md](docs/API.md). PostgreSQL e Redis ficam acessíveis apenas na rede interna do Compose. O serviço `scheduler` roda o agendador do Laravel, que apaga todos os dias as buscas de visitantes com mais de uma semana.

Para interromper, pressione Ctrl+C. Os dados ficam preservados nos volumes do Docker. Para iniciar de novo, use `docker compose up`.

## Usar a aplicação

1. Na página inicial, escolha Boa Viagem como origem e Recife Antigo como destino.
2. Clique em Buscar Rota, selecione os modais e clique em Buscar Trajeto.
3. Defina a saída, compare as opções e abra as instruções de uma delas. Em Mapa, consulte o percurso calculado e use os controles de aproximação.
4. Entre em Perfil e crie uma conta. Não há usuário ou senha padrão.
5. Em Rotas, adicione um local favorito. Em Perfil, altere e salve as preferências.
6. Consulte Mapa, Horários e Pontos de integração.

Horários, valores, linhas, coordenadas e disponibilidade de bicicletas são demonstrativos. O cálculo considera o horário de operação, a frequência das linhas, o tempo e a integração tarifária entre modais e a existência de bicicletas e vagas. A espera e a integração estão incluídas na duração total. O mapa usa as coordenadas cadastradas e o GeoJSON calculado sobre o mapa de fundo do OpenStreetMap, com conexões esquemáticas entre paradas. Esta versão não fornece transporte em tempo real, geocodificação de endereços livres ou traçado viário oficial.

## Executar sem Docker

Requisitos desta versão: PHP 8.5, Composer 2, Node.js 24, PostgreSQL 18 com PostGIS 3.6 e Redis 8. Extensões PHP: PDO PostgreSQL, PhpRedis, mbstring, intl, XML, DOM, fileinfo, ctype, tokenizer e OpenSSL.

Crie um banco e um usuário PostgreSQL que possa habilitar a extensão PostGIS. Inicie o Redis local e confira as variáveis `REDIS_*` no `.env`. Depois:

```bash
cp .env.example .env
# Edite DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD no .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8002
```

Para a limpeza diária das buscas de visitantes, deixe o agendador rodando em outro terminal com `php artisan schedule:work`.

Em outro terminal, inicie o frontend:

```bash
cd resources/frontend
npm ci
BACKEND_URL=http://127.0.0.1:8002 npm run dev
```

Abra http://localhost:3000. Os scripts de desenvolvimento e build copiam as fontes e imagens locais de `public/assets`; nenhum recurso depende de links temporários do Figma.

## Banco e documentação

- [Arquitetura e escopo](docs/ARQUITETURA.md)
- [Endpoints da API](docs/API.md)
- [Modelagem lógica](docs/BANCO_DE_DADOS.md)
- [Plano das entregas](docs/ENTREGAS.md)
- [Guia para a equipe](docs/GUIA_EQUIPE.md)
- [Validação](docs/VALIDACAO.md)
- `database/sql/01_schema.sql`: criação das tabelas, PostGIS, índices e restrições em PostgreSQL.
- `database/sql/02_seed.sql`: dados demonstrativos, sem contas ou senhas.
- `database/migrations` e `database/seeders`: fonte principal da estrutura e da carga de dados.

Os scripts SQL são uma alternativa às migrations em um banco vazio. Para o uso normal da aplicação, prefira `php artisan migrate --seed`.

## Testes

```bash
docker compose run --rm --build test
docker compose run --rm test composer test:pgsql
```

A imagem da API não leva os pacotes de desenvolvimento, então os testes rodam no serviço `test`, construído com eles a partir do mesmo `Dockerfile`. O primeiro comando roda `composer test`. `composer test` roda a suíte em SQLite e cache em memória. Os testes que dependem do PostgreSQL (restrições CHECK, PostGIS e `jsonb`) ficam ignorados nela.

`composer test:pgsql` roda a mesma suíte no PostgreSQL com PostGIS, no banco descartável `elomob_test`, que é criado e migrado automaticamente se ainda não existir. A configuração fica em `phpunit.pgsql.xml`. Sem Docker, o comando usa o host, o usuário e a senha do `.env`, e esse usuário precisa poder criar bancos e habilitar o PostGIS. Nenhum dos dois comandos usa o banco da aplicação, e os testes não devem ser configurados para usá-lo.

Para conferir o frontend:

```bash
cd resources/frontend
npm run typecheck
npm test
npm run build
```

Os testes de ponta a ponta usam o Playwright contra a aplicação inteira, com o Compose rodando. Na primeira vez, instale os navegadores do Playwright:

```bash
cd resources/frontend
npx playwright install chromium firefox
npm run test:e2e
```

Eles percorrem a jornada de visitante, a conta inteira (cadastro, necessidade de acessibilidade, favorito, histórico, troca de senha e exclusão) e o mapa, no Chrome de desktop, no Firefox e num celular emulado. Os tiles do mapa são respondidos pelos próprios testes, sem acessar o OpenStreetMap. Cada execução cria e exclui a própria conta e deixa algumas buscas de visitante, que a limpeza diária apaga depois de uma semana. Para testar outro endereço, use `E2E_BASE_URL`.

O GitHub Actions (`.github/workflows/ci.yml`) roda essas verificações a cada envio para a `main` e em cada pull request: Pint, as duas suítes PHP, TypeScript, testes e build do frontend, e o Docker Compose subindo do zero, com o frontend, o proxy e a API respondendo e os testes de ponta a ponta.

O relatório detalha a validação em `docs/VALIDACAO.md`.

## Contribuir

O padrão de commits, os testes a rodar antes de enviar e o cuidado com credenciais estão no [Guia para a equipe](docs/GUIA_EQUIPE.md#como-contribuir).
