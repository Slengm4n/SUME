
![Logo](https://i.postimg.cc/P5R118yy/SUME-1.png)



# SUME - Sistema Único de Merenda Escolar

Este sistema foi desenvolvido para auxiliar escolas e instituições no controle de refeições, com o objetivo de minimizar o desperdício de alimentos e otimizar a gestão de recursos. Através de um processo simplificado de registro e confirmação de refeições, o sistema ajuda administradores e gestores a prever a demanda de forma mais precisa, garantindo que apenas a quantidade necessária de alimentos seja preparada.

## Benefícios:
- **Redução do Desperdício**: Com base na confirmação de participação dos usuários, o sistema permite preparar refeições na quantidade adequada.
- **Eficiência Operacional**: Facilita o planejamento de refeições diárias, economizando tempo e recursos.
- **Responsabilidade Ambiental**: A diminuição do desperdício de alimentos contribui para práticas sustentáveis e para a educação em conscientização ambiental dentro das instituições.
  
  
Esse sistema é projetado para fácil utilização, permitindo que administradores configurem horários de refeições e que os usuários confirmem suas presenças com praticidade.

## Stack utilizada

**Front-end:** HTML, CSS, JavaScript

**Back-end:** PHP, JavaScript


## Instalação

Instale a SUME

```bash
1. Clone o repositório:

    git clone https://github.com/Slengm4n/SUME.git
   
    - Utilize um servidor local como XAMPP ou WampServer, que já vêm pré-configurados com PHP, MySQL e Apache para facilitar o ambiente de desenvolvimento local.

    - Instale o XAMPP ou WampServer, inicie o Apache e o MySQL pelo painel de controle, e aponte o navegador para `http://localhost/SUME` para acessar o projeto localmente.
  
```
## Estrutura do Banco de Dados

Este projeto utiliza um banco de dados MySQL com as tabelas descritas abaixo. Certifique-se de criar essas tabelas e seus respectivos campos para que o sistema funcione corretamente.

### Tabelas

#### 1. **users** (Tabela de Usuários)
Armazena informações sobre os usuários do sistema.

| Campo        | Tipo       | Descrição                              |
|--------------|------------|----------------------------------------|
| `id`         | INT (PK)   | Identificador único do usuário         |
| `name`       | VARCHAR(50)| Nome do usuário                        |
| `email`      | VARCHAR(100) | E-mail do usuário                    |
| `password`   | VARCHAR(255) | Senha criptografada do usuário       |
| `created_at` | TIMESTAMP  | Data de criação do registro            |

#### 2. **meals** (Tabela de Refeições)
Armazena informações sobre as refeições cadastradas pelo administrador.

| Campo        | Tipo       | Descrição                              |
|--------------|------------|----------------------------------------|
| `id`         | INT (PK)   | Identificador único da refeição        |
| `name`       | VARCHAR(100) | Nome ou descrição da refeição        |
| `date`       | DATE       | Data em que a refeição será servida    |
| `created_at` | TIMESTAMP  | Data de criação do registro            |

#### 3. **confirmations** (Tabela de Confirmações de Refeições)
Registra as confirmações de presença dos usuários para cada refeição.

| Campo        | Tipo       | Descrição                              |
|--------------|------------|----------------------------------------|
| `id`         | INT (PK)   | Identificador único da confirmação     |
| `user_id`    | INT (FK)   | ID do usuário que confirmou a presença |
| `meal_id`    | INT (FK)   | ID da refeição confirmada              |
| `confirmed_at` | TIMESTAMP | Data e hora da confirmação            |

### Relacionamentos
- **users** e **confirmations**: Relacionamento um-para-muitos. Cada usuário pode fazer várias confirmações.
- **meals** e **confirmations**: Relacionamento um-para-muitos. Cada refeição pode ter várias confirmações de presença.

Scripts de Criação das Tabelas
Aqui estão os scripts SQL para criar essas tabelas:
```
SQL
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE meals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE confirmations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    meal_id INT,
    confirmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (meal_id) REFERENCES meals(id)
);
```


    
## Demonstração

![GIF](https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExeXM3a2p1YWJwM3Exc3E2eHFhazd4dGUyeGFpeGtoYXZndTFkZ2lyZyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/Hxc5ZVcxzkdPDU9VoX/giphy-downsized-large.gif)


## Autores

- [@Slengm4n](https://www.github.com/Slengm4n)

- [@Ardoh4in](https://github.com/Ardoh4in)

- [@HeitorZinn](https://github.com/HeitorZinn)

- [@ManenttiiBR](https://github.com/ManenttiiBR)

## Licença

[MIT](https://choosealicense.com/licenses/mit/)

