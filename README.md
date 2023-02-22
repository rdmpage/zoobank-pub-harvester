# ZooBank Publication Harvester

Retrieve publications from ZooBank.

## Download ZooBank

To get the list of publication ids we download ZooBank from ChecklistBank https://www.checklistbank.org/dataset/2037/download in the original archive format.

## Process

We parse the file `taxon.txt` to extract publication ids, then fetch both JSON (via API) and HTML to get DOIs (if they exist), more precise dates, and ISSNs.


## Next

We can parse the ZooBank data into CSL-JSON, or do other things (such as process for Wikidata).
