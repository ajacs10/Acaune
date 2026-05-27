# Sistema de Gestão Académica Universitária

## Descrição do Projeto

O AcaUni é um sistema de gestão académica universitária moderno,Desenvolvido para a P2 (segunda Frequencia), com o objetivo de digitalizar, automatizar e centralizar todos os processos académicos e administrativos da instituição.

A plataforma permite a gestão eficiente de estudantes, professores, cursos, disciplinas, matrículas, notas e relatórios institucionais num único ambiente digital.

Base modular em HTML, CSS, JavaScript, PHP e MySQL.

🎯 Objetivo do Sistema

Criar uma plataforma académica robusta que permita:

- Gestão de estudantes e professores
- Organização de cursos e disciplinas
- Matrículas por semestre
- Lançamento e cálculo de notas
- Relatórios académicos e institucionais
- Painel administrativo centralizado

💡 Identidade do Produto

O AcaUni representa uma solução moderna de gestão universitária focada em:

- transformação digital
- eficiência institucional
- organização académica inteligente
- experiência de utilizador premium

🧱 Tecnologias

Frontend: HTML5, CSS3, JavaScript (ES6+)
Backend: PHP com API simples
Base de dados: MySQL
Arquitetura: frontend separado do backend

🎨 Estilo Visual

Interface enterprise premium
minimalismo moderno
glassmorphism subtil
interface limpa e profissional
inspiração em sistemas como Notion, Stripe e Google Workspace

🚀 Visão Final

O Acaune será uma plataforma universitária completa, escalável e pronta para uso real, com foco em:

- performance
- segurança
- escalabilidade
- usabilidade
- design de nível internacional

👩‍💻 Desenvolvimento

Projeto desenvolvido por Ana Juliana Avelino de Acosta Sobrinho, para uma prova de programação.

## Estrutura

- `frontend/html/` páginas HTML
- `frontend/css/` estilos CSS
- `frontend/js/` JavaScript e chamadas `fetch`
- `backend/php/conexao.php` conexão centralizada com MySQL
- `backend/php/app/` código PHP da API
- `backend/mysql/schema.sql` script da base de dados
- `public/api/` entrada dos endpoints da API
- `templete_ideia/` imagens e referência visual do template

## Inclui

- login de demonstração via `POST`
- dashboard premium responsivo
- gestão de estudantes, matrículas, notas, relatórios, perfil e configurações
- API em PHP separada do frontend
- CSS e JavaScript externos

## Como executar

### Com Docker

Esta é a forma recomendada para correr tudo, incluindo PHP, MySQL e phpMyAdmin.

1. Iniciar a stack:

```bash
./iniciar.sh
```

ou diretamente:

```bash
docker compose up --build
```

2. Aceder aos serviços:

- Frontend: `http://127.0.0.1:8088/frontend/html/login.html`
- API: `http://127.0.0.1:8088/api/dashboard`
- Teste da base de dados: `http://127.0.0.1:8088/testar-conexao`
- phpMyAdmin: `http://127.0.0.1:8081`

3. Credenciais da BD no container:

- Host: `mysql`
- Base de dados: `AcaUni`
- Utilizador: `root`
- Palavra-passe: vazia

O schema é importado automaticamente a partir de `backend/mysql/schema.sql` quando o container MySQL é criado pela primeira vez.

O projeto foi organizado para apresentação em Windows com XAMPP/phpMyAdmin ou em Linux.

1. Criar o ficheiro de ambiente:

```bash
cp .env.example .env
```

No Windows, o script `iniciar.bat` cria o `.env` automaticamente se ele ainda não existir.

2. Importar a base de dados:

- Pelo phpMyAdmin: abra `http://localhost/phpmyadmin`, clique em Importar e escolha `backend/mysql/schema.sql`.
- Pelo terminal Windows/XAMPP: execute `importar-bd.bat`.
- Pelo terminal Linux: execute `./importar-bd.sh`.

3. Iniciar o projeto com um comando:

```bash
./iniciar.sh
```

No Windows:

```bat
iniciar.bat
```

Se a porta `8088` estiver ocupada, escolha outra porta:

```bash
PORT=8090 ./iniciar.sh
```

No Windows:

```bat
set PORT=8090
iniciar.bat
```

Depois abra:

- Frontend: `http://127.0.0.1:8088/frontend/html/login.html`
- API: `http://127.0.0.1:8088/api/dashboard`
- Teste da base de dados: `http://127.0.0.1:8088/testar-conexao`

Se aparecer erro de ligação à base de dados:

1. Abra o XAMPP.
2. Inicie `Apache` e `MySQL`.
3. Abra `http://localhost/phpmyadmin`.
4. Importe o ficheiro `backend/mysql/schema.sql`.
5. Confirme que existe uma base chamada `AcaUni`.
6. Verifique o `.env`:

```env
DB_HOST=127.0.0.1
DB_NAME=AcaUni
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

## Envio por POST

O login, o registo de utilizador e o registo de estudante são enviados por `POST` usando JavaScript:

- `POST /api/login`
- `POST /api/register`
- `POST /api/students`

A ligação com MySQL está centralizada em `backend/php/conexao.php`.

Regra de palavra-passe no registo:

- mínimo de 8 caracteres
- pelo menos 1 letra maiúscula
- pelo menos 1 letra minúscula
- pelo menos 1 número
- pelo menos 1 caractere especial

## Utilizador inicial

Após importar `backend/mysql/schema.sql`, o sistema cria uma conta administradora inicial:

- Nome: Ana Juliana Avelino Da Costa Sobrinho
- Email: `ajacs@gmail.com`
- Palavra-passe: `Ana@01135`

No ficheiro SQL a palavra-passe não fica guardada em texto puro; ela é inserida na tabela `users` com hash seguro.
