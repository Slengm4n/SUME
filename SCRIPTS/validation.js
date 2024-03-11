const validation = new JustValidate("#signup");

validation
    .addField("#name", [
        {
            rule: "obrigatorio"
        },
        {
            rule: "nome"
        }
])
    .addField("#email", [
        {
            rule: "obrigatorio"
        },
        {
            rule: "email"
        }
    ])
    .addField("#password",[
        {
            rule: "required"
        },
        {
            rule: "password"
        }
    ]);