.PHONY: all build test clean install uninstall

all: build

build:
	@mkdir -p ./dist;./makelos release:generate --format=tar --path=./dist;
	@echo "Akelos built. Run \"sudo make install\" to install it."

install:
	@mkdir -p /usr/local/lib/akelos
	@git archive master | tar -x -C /usr/local/lib/akelos;
	@echo 'Akelos installed at /usr/local/lib/akelos.'
	@ln -s /usr/local/lib/akelos/akelos /usr/local/bin/akelos
	@ln -s /usr/local/lib/akelos/makelos /usr/local/bin/makelos
	@echo 'Linking akelos and makelos in /usr/local/bin/.'
	@echo 'Done.'

uninstall:
	@echo 'Removing /usr/local/lib/akelos, /usr/local/bin/akelos, /usr/local/bin/makelos.'
	@rm -rf /usr/local/lib/akelos
	@rm /usr/local/bin/akelos
	@rm /usr/local/bin/makelos
	@echo 'Done.'

clean:
	@rm -rf dist

test:
	@./makelos test:units