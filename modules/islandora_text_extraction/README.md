# islandora_text_extraction
### Connects Islandora 8 to Hypercube microservice and extracts text from PDFs

Install module in the usual way, 
then copy `assets/ca.islandora.alpaca.connector.ocr.blueprint.xml` 
to `/opt/karaf/deploy` on the server. 
 _note:_ This config file assumes a url of `http://localhost:8000/hypercube`.  
If your service is found elsewhere this must be changed.
There is no need to restart.
  
In the usual Ansible build this will require no modification.

If a parent node is tagged as `Digital Document` an `Image` tagged media
will extract text from that image at the time of ingestion.  
The content type of the parent node should be configured to allow multiple tags.

_note:_ Media are linked to their parent nodes with the `Media Of` 
entity reference field.  If you wish to attach the PDF (or any other ) media type
to a parent node which has any content type other than Repository Item 
(islandora_object) the parent content type will have to be added to the `Media Of`
field in the media type description.

## Prepare module for PDF text extraction
Install `texttopdf` on your server if not already present.
On an ubuntu/debian machine like the default claw playbook run 
`sudo apt-get install poppler-utils`

test to see its been properly installed with `which pdftotext`

Install php libraries with  `composer require spatie/pdf-to-text`

In the unlikely event that your `pdftotext` binary exists on your server 
outside of the system path, the path to the binary can be set at 
`/admin/config/islandora/text_extraction`.

## Using text extraction ##
The containing document must be tagged as `Digital Document`, 
and the media must be tagged as `Original File`.
A new editable `Extracted Text` media will be created and attached when `PDF` or 
`Image` media types are added to a node.




