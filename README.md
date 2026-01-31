# niceLattes

Sistema PHP para exibição de Currículo Vitae acadêmico baseado no formato XML da Plataforma Lattes.

## Funcionalidades

- Exibição do CV em layout acadêmico moderno e responsivo
- Painel administrativo para controle de visibilidade de seções e itens
- Suporte a todas as seções do Lattes (formação, artigos, software, bancas, eventos, etc.)
- Design clássico acadêmico com tipografia serifada

## Requisitos

- PHP 7.4 ou superior
- Extensão SimpleXML habilitada
- Extensão mbstring habilitada

## Instalação

1. Copie os arquivos para o servidor web:
   ```
   index.php
   admin.php
   config.ini
   includes/
   assets/
   data/
   ```

2. Copie `config.ini.example` para `config.ini` e configure:
   ```ini
   admin_pin = seu_pin_aqui
   cv_xml_file = seu-arquivo-lattes.xml
   ```

3. Coloque o arquivo XML do Lattes na pasta raiz

4. Certifique-se que a pasta `data/` tem permissão de escrita

## Uso

- **Página pública**: Acesse `index.php` para visualizar o CV
- **Painel admin**: Acesse `admin.php` e entre com o PIN para gerenciar visibilidade

## Estrutura

```
niceLattes/
├── index.php              # Página pública do CV
├── admin.php              # Painel administrativo
├── config.ini             # Configuração (não commitado)
├── config.ini.example     # Exemplo de configuração
├── includes/
│   ├── config.php         # Funções e constantes
│   └── cv-parser.php      # Parser do XML Lattes
├── assets/
│   └── style.css          # Estilos CSS
└── data/
    ├── visibility.json    # Configuração de visibilidade
    └── .htaccess          # Proteção do diretório
```

## Licença

MIT
