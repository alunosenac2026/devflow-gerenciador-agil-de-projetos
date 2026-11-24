# Instruções para agentes de IA no DevFlow

Responda e documente em português brasileiro. Leia o pedido, este arquivo e os documentos pertinentes em `docs/`. Antes de editar, confira a estrutura, as dependências e o estado do Git. Apresente um plano objetivo. Quando uma informação indispensável estiver ausente, esclareça-a antes de assumir.

## Fluxo de trabalho

1. Converse, planeje e registre as decisões pertinentes ao escopo.
2. Execute em etapas pequenas e verificáveis. Preserve as convenções e o trabalho existente e solicite autorização antes de mudanças estruturais ou potencialmente destrutivas.
3. Revise o diff, execute as verificações aplicáveis, corrija os problemas encontrados e informe os resultados com evidências. Identifique verificações pendentes.
4. Use o Git para rastreabilidade. Confira a branch e o diff antes de qualquer registro. Faça staging ou commit somente quando permitido pelo pedido vigente e inclua apenas mudanças da tarefa. Nunca execute push, reset destrutivo, exclusão de dados ou alteração de credenciais sem confirmação explícita. Preserve a branch atual, salvo instrução do usuário.

## Segurança e revisão

- Use PDO e consultas parametrizadas. Valide entradas no servidor e mantenha restrições de integridade no banco. Renderize dados do usuário como texto na interface.
- Mantenha configuração local, credenciais, logs e código interno fora da raiz pública efetiva. Verifique os caminhos alternativos do Apache antes de propor mudanças. Solicite aprovação antes de aplicar uma configuração estrutural.
- Revise o isolamento local do checkout em HTTP e HTTPS e do banco. Verifique host padrão, host da aplicação e interfaces de rede. Nesta etapa, registre as medidas propostas e aguarde autorização antes de alterar o XAMPP ou o banco.
- Revise a validação de origem e de `Content-Type` nas escritas conforme o [contrato da API](docs/arquitetura.md). Revise também o tratamento de erros e a exposição de detalhes internos.
- Em auditorias, avalie entradas, SQL Injection, XSS, CSRF, erros, credenciais, dependências e organização. Examine autenticação, autorização e sessões quando aplicáveis. Registre severidade, arquivo, evidência, impacto e correção. Distinga hipóteses de achados confirmados.
- Revise a parametrização de todas as consultas que recebem valores variáveis. Use banco exclusivo para testes de escrita e dados descartáveis. Use a [matriz de cobertura](docs/testes.md) e relate o que foi efetivamente executado por requisito.
