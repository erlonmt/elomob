# Plano de entregas do ELOMOB

O planejamento usa as datas combinadas pela equipe: 01/11, 16/11 e 30/11/2026. O edital lista 30/10, 13/11 e 27/11. A divergência fica registrada para que a equipe confirme o calendário com o professor, sem alterar o conteúdo do edital.

| Data usada pela equipe | Entrega | Situação desta versão |
| --- | --- | --- |
| 01/11/2026 | Repositório, integrantes, modelagem lógica e DDL | Repositório publicado no GitHub com a modelagem lógica (`docs/BANCO_DE_DADOS.md`) e os scripts de `database/sql`; falta confirmar os cinco integrantes como colaboradores |
| 16/11/2026 | Endpoints principais e conexão com o banco | 19 endpoints REST em `/api/v1`, documentados em `docs/API.md` e testados no PostgreSQL com PostGIS; o código da aplicação entra no repositório nesta entrega |
| 30/11/2026 | MVP integrado | Front-end Next.js consumindo a API nas 11 telas, com dados de demonstração; jornada e acessibilidade verificadas no Chrome e no Firefox, no celular e no desktop; falta a homologação da equipe em outro computador |
| 01 a 08/12/2026, conforme edital | Apresentação oficial | Atualizar o template de documentação técnica, preparar o pitch e ensaiar a demonstração |

## Critérios do edital

| Critério | Peso | Evidência planejada |
| --- | --- | --- |
| Funcionalidade e aderência | 3,0 | Demonstrar a jornada do protótipo da AV1 nas 11 telas, com interações reais |
| Qualidade do código | 3,0 | Controllers por recurso, FormRequests, API Resources, Policies, serviços de cálculo e catálogo, componentes React, testes e integração contínua no GitHub Actions |
| Estrutura e persistência | 2,0 | PostgreSQL com PostGIS, migrations, scripts SQL, relações, restrições e dados preservados depois de reiniciar |
| Pitch e domínio técnico | 2,0 | Cada integrante explicar decisões e demonstrar sua parte |

## Pendências

**Antes do Checkpoint 2:** confirmar os cinco integrantes como colaboradores e o acesso do professor ao repositório. Definir o papel de cada integrante e registrá-lo no README. Enviar o código da aplicação em commits separados por assunto, no padrão do [guia da equipe](GUIA_EQUIPE.md#como-contribuir), e conferir se a primeira execução do GitHub Actions passa.

**Antes do Checkpoint 3:** executar a aplicação em outro computador seguindo apenas o README e comparar a jornada com o protótipo do Figma.

**Antes da apresentação:** revisar com a equipe a documentação técnica da AV2 (`Elomob - Documentação AV2.docx`), que atualiza o template da AV1 com a arquitetura da solução, como pede o edital. Os arquivos em Word ficam fora do repositório, porque trazem as matrículas dos integrantes. Preparar o pitch e ensaiar a demonstração ao vivo.

O edital pede a solução concebida na AV1, mas o enunciado completo do desafio municipal não foi fornecido. Confirmar com o professor quais integrações externas são exigidas e se o mapa esquemático, a fonte de dados demonstrativa e a ausência de informações em tempo real são aceitáveis. Não afirmar que esses recursos estão integrados a serviços oficiais.

## Sugestão de divisão entre cinco integrantes

| Papel | Responsabilidade |
| --- | --- |
| Front-end | Tela inicial, busca, navegação e responsividade |
| Back-end | Cálculo de caminhos, validações e endpoints |
| Banco de dados | Modelo, migrations, scripts e carga |
| QA | Cenários, testes e homologação em outra máquina |
| Líder técnico | Integração, documentação, revisão e pitch |

Os papéis são uma sugestão; a formação original da AV1 deve ser mantida conforme o edital.
