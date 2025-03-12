## 1. Wrzucenie pliku z danymi
Plik z danymi należy umieścić w katalogu:
```
var/data/
```

## 2. Uruchomienie komendy
Aby przetworzyć dane, uruchom poniższą komendę:
```
php bin/console app:process-service-data var/data/recruitment-task-source.json
```

## 3. Uruchomienie testów
Testy można uruchomić indywidualnie:
```
php bin/phpunit tests/ProcessServiceTest.php
php bin/phpunit tests/ProcessServiceReportsCommandTest.php
```

Lub wszystkie na raz:
```
php bin/phpunit
```

