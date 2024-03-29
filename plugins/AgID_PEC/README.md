# Qualtrics integration
 
### Introduzione
Questo documento descrive un esempio di integrazione di ```AgID_Limesurvey``` con **Qualtrics**. Dal link d'invito inviata tramite il sistema Limesurvey, in Qualtrics vengono visualizzati nel primo blocco i dati dell'utente autenticato precedentemente con ```SpiD```.



## HTML

Inserire nel blocco di "benvenuto" il codice html contenuto in ```qualtrics_welcome_block.html```.
I dati dell'utente verranno stampati nel TAG ```<span id="USRINFO"></span>```


## Javascript

Aprire l'editor JS nel blocco di "benvenuto" e sostituire il codice contenuto in ```qualtrics.js```.
Se il contenuto precedente è stato inserito per ragioni specifiche inserire ESCLUSIVAMENTE lo snippet nel metodo
```Qualtrics.SurveyEngine.addOnReady```

dal commento 

```/*** Limesurvey integration starts here ***/ ```.


## Struttura JSON
Questa integrazione avviene tramite la trasmissione di un file JSON criptato in **query string** con chiave ```sid```


### Esempio:

**Token query parameter**

``sid=ewsdcgfhvgeyJmaXNjYWxudW1iZXIiOiIxMTEyMjIzMzMyMjIzMzQ0IiwibmFtZSI6IkNsYXVkaW8iLCJtb2JpbGVwaG9uZSI6IjMzMzQzMTk2OTciLCJlbWFpbCI6ImFtaW5lQHdpbmsuYnkiLCJmYW1pbHluYW1lIjoiQmlhbmNhbGFuYSIsInNwaWRjb2RlIjoiRENCQTAxMjM0NTY3ODkifQ``


**JSON**

```
{
    "fiscalnumber": "1112223332223344",
    "name": "Mario",
     "familyname": "Rossi",
    "mobilephone": "1111100000",
    "email": "mariorossi@email.net",
    "spidcode": "ABCD121312312ZZ0A"
}

```

## Sviluppo custom

Per usare i dati in un altro modo rispetto a quello mostrato nell'esempio sostituire il codice dopo il seguente commento

```/*** replace this code if you want to show or use JSON data in an other way ***/```


 

