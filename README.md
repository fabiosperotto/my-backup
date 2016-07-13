# my-backup
Uma ferramenta (CLI) simples para a sua rotina diária de backups. Usa como base o [PHPBU](http://phpbu.de/), uma framework em PHP para backups.

## Por que usar my-backup e não diretamente PHPBU?
É uma ótima pergunta. Quer uma ferramenta fácil de utilizar manualmente ou via cron job, para organizar e executar múltiplos backups de uma vez só? Se sim, aqui é a sua casa, do contrário, vá direto para o PHPBU e divirta-se.

## Para quem?
Feito de desenvolver para desenvolvedor. 

## Pré-requisitos
- Os mesmos que PHPBU
- PHP >= 5.5.9
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension

## Estrutura
- /backup: arquivos de configurações e diretório padrão onde os backups serão criados.
- /src: codificação da aplicação.

## Usando my-backup
1. Clone o projeto, execute composer install, ou baixe algum release e descompacte em seu servidor
2. crie um arquivo backups.json com as configurações de banco de dados e servidores que precisar. Use como exemplo backups.json.example. Pode ser necessário aplicar permissão de escrita no diretório /backup.
3. Execute php run.php.

Exemplo:
```bash
$ wget https://github.com/fabiosperotto/my-backup/archive/0.1.0.tar.gz -O - | tar -xz
$ cd my-backup/
$ cp backup/backups.json.example backup/backups.json
$ vim backup/backups.json
$ php run.php
```

[![asciicast](https://asciinema.org/a/4qswbn32t0qaa2yqa93bty4gn.png)](https://asciinema.org/a/4qswbn32t0qaa2yqa93bty4gn)

## Créditos
Thanks to [Sebastian](https://github.com/sebastianfeldmann/phpbu) for this amazing PHPBU.

