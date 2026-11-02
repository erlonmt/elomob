# Guia da equipe ELOMOB

A equipe deve conseguir executar e explicar cada parte da aplicação. Use este roteiro para revisar a entrega e distribuir o estudo.

## Primeira execução

O repositório fica em https://github.com/erlonmt/elomob. Se ainda não tiver acesso, peça a Erlon o convite de colaborador. Depois, clone o repositório e suba os serviços:

```bash
git clone https://github.com/erlonmt/elomob.git
cd elomob
docker compose up --build
```

Espere a aplicação indicar a porta 8000. Abra http://localhost:8000 e crie uma conta de teste pela tela de cadastro, pois não há usuário padrão. Para executar sem Docker, siga o [README](../README.md#executar-sem-docker).

## Roteiro de apresentação

1. Mostre o problema de mobilidade e o protótipo da AV1.
2. Busque Boa Viagem → Recife Antigo e compare os critérios.
3. Abra o trajeto de ônibus e metrô e explique por que os totais correspondem aos passos: na Joana Bezerra, o metrô aparece como integração tarifária, sem nova tarifa, e o trajeto custa R$ 3,80.
4. Consulte linhas e paradas, deixando claro que a carga é demonstrativa.
5. Crie uma conta, salve um local e atualize preferências.
6. Marque um tipo de necessidade no perfil e repita a busca Boa Viagem → Recife Antigo só com ônibus e metrô e saída às 14h30. O detalhe de cada etapa mostra o que ela atende e avisa quando há escada.
7. Reinicie os serviços e mostre que conta e favoritos foram preservados.
8. Em Rotas, limpe o histórico. No perfil, troque a senha e exclua a conta: os favoritos e as buscas são apagados junto (LGPD).
9. Apresente a modelagem, a separação entre validação e cálculo e os testes.

Trajeto mais rápido de Boa Viagem ao Recife Antigo (ônibus e metrô, saída às 14h30) para cada tipo de necessidade. Em outros horários, as esperas podem mudar a escolha.

| Tipo marcado | Trajeto | Por quê |
| --- | --- | --- |
| Nenhum | 042 até Joana Bezerra, Linha Centro até a Estação Recife e caminhada | É o mais rápido |
| Mobilidade reduzida | 042 até Joana Bezerra, **Linha Sul** até a Estação Recife e caminhada | A plataforma da Linha Centro só tem escada; a da Linha Sul tem elevador |
| Deficiência visual | 042, caminhada até Afogados, 033 até o Derby e 081 até o Recife Antigo | A travessia da Estação Recife não tem semáforo sonoro, e o Cais de Santa Rita não tem piso tátil |
| Cadeira de rodas | **061 pela Via Mangue** até o Cais de Santa Rita e caminhada | A entrada da Estação Recife tem degraus, e a 033 não tem elevador |
| Deficiência auditiva | **061 pela Via Mangue** até o Cais de Santa Rita e caminhada | A 042 não tem letreiro |

Saindo de Cordeiro só de ônibus, quem tem deficiência auditiva troca a 081, que também não tem letreiro, pelo **BRT da Caxangá** até a Boa Vista e segue a pé.

## Pontos que cada integrante deve saber explicar

React e Next.js apresentam a interface responsiva e consomem a API REST Laravel. O proxy do Next.js preserva a sessão e o token CSRF. Laravel valida as entradas, consulta PostgreSQL com PostGIS e guarda no Redis as sessões, o catálogo temporário e a rede usada no cálculo, que é renovada no cache quando os dados da rede mudam. O serviço de cálculo percorre conexões da rede e persiste os resultados em uma transação. Senhas usam hash. O usuário só lê e altera os próprios favoritos e buscas. As tarifas são inteiros em centavos.

Uma única busca encontra os trajetos que nenhum outro supera ao mesmo tempo em duração, custo, transferências e sustentabilidade (a fronteira de Pareto), e cada critério escolhe o seu entre eles. Os estados saem de uma fila de prioridade pela chegada mais cedo. O estado inclui o serviço anterior para distinguir continuar na mesma linha de embarcar novamente, e os rótulos de Pareto preservam alternativas que alcançam conexões antes do fechamento. Horário de operação, frequência, tempo de integração entre modais, integração tarifária e disponibilidade de bicicletas influenciam o resultado. Na Joana Bezerra, ônibus e metrô custam uma só tarifa: quem troca paga apenas a diferença até a tarifa mais alta. A espera e a integração já fazem parte da duração exibida. O indicador ambiental é uma heurística demonstrativa, não uma medida oficial de emissões.

Os detalhes estão em [ARQUITETURA.md](ARQUITETURA.md), [API.md](API.md) e [BANCO_DE_DADOS.md](BANCO_DE_DADOS.md).

## Como contribuir

Faça um commit para cada mudança, no padrão Conventional Commits em português e sem escopo: `tipo: descrição`, com tipos como `feat`, `fix`, `refactor`, `docs` e `test`. Por exemplo, `feat: cadastra os sentidos inversos das integrações`. Quando o motivo não for óbvio, explique-o no corpo, com linhas de até 72 caracteres. Termine com o rodapé da entrega, como `Refs: Checkpoint 2 da AV2`.

Antes de enviar, rode os testes. As imagens do Docker copiam o código em vez de usar a pasta do projeto, então reconstrua os serviços depois de alterar a aplicação:

```bash
docker compose up --build -d
docker compose exec app composer test
docker compose exec app composer test:pgsql
cd resources/frontend
npm ci
npm run typecheck
npm test
```

O GitHub Actions repete essas verificações a cada envio para a `main` e em cada pull request, e também sobe o Docker Compose do zero. Um X vermelho ao lado do commit no GitHub indica falha; abra a execução para ver qual etapa quebrou.

Não envie o `.env` nem senhas reais. O `.env.example` lista as variáveis, e as senhas do `compose.yaml` valem só para o ambiente local.

## Próximas decisões do grupo

Definir a fonte oficial de transporte e verificar as condições de acesso à API. O mapa já mostra pontos e trajetos sobre o mapa de fundo do OpenStreetMap e permite aproximar e deslocar; as conexões são esquemáticas, sem navegação por ruas. Decidir se recuperação de senha e administração de dados entram no escopo. Registrar essas escolhas na documentação técnica da AV2, o arquivo em Word guardado fora do repositório.
