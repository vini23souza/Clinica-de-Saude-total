Sistema de Gestão para Clínica de Saúde

Este projeto é um *sistema web para gerenciamento de uma clínica de saúde, desenvolvido com **PHP, Python, JavaScript, MySQL, HTML e CSS*.

O sistema permite o controle de pacientes, médicos, relatórios e dashboard administrativo, além de contar com *scripts em Python* para automações e processamento de dados.

---

## 🚀 Tecnologias Utilizadas

- PHP  
- Python  
- JavaScript  
- HTML5  
- CSS3  
- MySQL / MySQLi  
- XAMPP  
- VS Code  

---

## ⚙️ Pré-requisitos

Antes de rodar o projeto, você precisa ter instalado:

- ✅ XAMPP (Apache + MySQL)
- ✅ Python 3.10 ou superior
- ✅ VS Code
- ✅ Git (opcional)

---

## 📥 Como baixar o projeto

No terminal do VS Code, rode:

```bash
cd C:\xampp\htdocs
git clone https://github.com/seu-usuario/Clinica-de-Saude-totall.git

Ou baixe o ZIP pelo GitHub e extraia em:

C:\xampp\htdocs\


---

🖥️ Como rodar o projeto PELO TERMINAL (VS Code)

1️⃣ Inicie o XAMPP pelo terminal

Você pode abrir o XAMPP manualmente ou pelo terminal:

cd C:\xampp
xampp_start.exe

Confirme que:

Apache ✅

MySQL ✅


estão iniciados.


---

2️⃣ Crie o banco de dados

No navegador (apenas uma vez para configurar), acesse:

http://localhost/phpmyadmin

Crie um banco chamado:

clinica_profissional

Depois importe o arquivo:

database/create_tables.sql


---

3️⃣ Configure o banco de dados

Abra no VS Code:

config/database.php

E ajuste se necessário:

<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "clinica_profissional";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}
?>


---

4️⃣ Inicie o sistema pelo terminal

No terminal do VS Code, execute:

cd C:\xampp\htdocs\clinica_profissional
php -S localhost:8000 -t public

Agora abra no navegador:

http://localhost:8000

✅ Seu sistema de clínica estará rodando pelo servidor local do terminal.


---

🐍 Rodando os scripts Python pelo terminal

Se quiser rodar os scripts do sistema:

cd C:\xampp\htdocs\clinica_profissional\python
python relatorios.py


---

👨‍💻 Autor

Nome: Vinícius Rafael
Curso: Analista de Sistema
Projeto: Sistema de Gestão para Clínica de Saúde
Tecnologias: PHP, Python, JavaScript, MySQL, HTML, CSS


---

✅ Status do Projeto

🚧 Em desenvolvimento – Projeto acadêmico
