# ![Islandora](https://cloud.githubusercontent.com/assets/2371345/25624809/f95b0972-2f30-11e7-8992-a8f135402cdc.png) Islandora
[![Build Status][1]](https://travis-ci.com/Islandora/islandora)
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

Islandora is a module that turns a Drupal 8 site into a control panel for your digital repository.  Through a user
interface, it allows repository administrators to
- Persist digital content in a Fedora repository
- Model digital content using core Drupal entities (nodes, media, files, and taxonomy terms).  Currently, there is
support for
  - collections
  - images
  - binary files (including PDfs)
  - audio
  - video
- Design forms for editing metadata 
- Control the display and theming of digital content
- Perform full text searching of content and metadata
- Bulk ingest content (using Drupal's migrate framework)
- Administer fine grained access control
- Index RDF metadata in a triplestore
- Generate derivative files, such as web quality representations.
  - Currently, only image derivatives are supported (requires islandora_image), but more to come.
- Apply bulk operations to lists of content (re-index content, regenerate derivatives, etc...) 
- And much, much more...

Content in an Islandora repository is treated as ordinary Drupal content, so the entire Drupal ecosystem of contributed
modules is at your disposal.  In fact, Islandora uses many contributed modules itself, including the extremely powerful
and flexible `context` module.  The `context` module allows users to do many things through a UI that normally would 
require programming custom modules or themes.  Want to show certain users a simplified form for data entry?  Want to 
give each collection a different theme?  Want to give anonymous users a restricted view?  All of this can be done using
the `context` module. It is similar to the `rules` module, and it allows repository administrators to filter repository events (view, create,
update, delete, etc...) by the criteria of their choice and respond by executing configurable actions.   

## Requirements / Installation

Setting up a full digital repository is a daunting task involving many moving parts on one or more servers.  To make things
easier to get started, you can fully bootstrap a complete repository solution using our Ansible installer, claw-playbook.
It can install both to a local Vagrant environment for development purposes, or it can install to one or more remote servers
by providing your own playbook.  By default you'll get one server with everything on it (i.e. the kitchen sink install). But
we have broken down each component into its own Ansible role, so more advanced users can create customized builds containing
only what their needs require.  See the README for more details.
  
## Configuration

If you want to get up and running as quickly as possible, import the `islanora_demo_feature` feature to install example configuration
and bootstrap your site. If you're starting from scratch, then _at a minimum_, you must:
1. Set the URL to your message broker at `admin/config/islandora`
1. Enable the `islandora_core_feature` module, then visit `admin/config/development/features` and import its config. It contains
everything required for basic content modeling. You can also use Drush to import the feature
`drush -y fim --bundle=islandora islandora_core_feature`. 
1. Run the migration to load the taxonomy terms required by Islandora.  This can be done by visiting `admin/structure/migrate`, or executed via drush
`drush -l http://localhost:8000 mim --group=islandora`. 

## Content Modeling

Islandora uses core Drupal 8 functionality for modeling content.  Most core content entities are utilized:

1. Nodes
    1. Nodes hold descriptive and structural metadata about objects in your repository.  Membership between nodes (e.g. members
of a collection, or pages of a book) is denoted with `field_member_of`, which is provided by `islandora_core_feature`.
Additional behavior can be controlled by tagging nodes with taxonomy terms using `field_tags`.
1. Media
    1. Media hold technical metadata for the files they represent.  There are four core media types, used for audio, video,
images, and generic files.  Media are associated with a node using `field_media_of`, which is provided by `islandora_core_feature`.
The role of the media is indicated by tagging it with a taxonomy term using `field_tags`.  For example,
tagging a media as 'Preservation Master' indicates that it is the master archival copy of a file, while 'Service File' would
indicate that it is a lower quality derivative intended to be shown to the average user.
1. Files
    1. Files hold the binary contents that are described by Media.  They often created along with a media to hold its technical metadata,
but can be created and then later associated with a Media in a separate process.
1. Taxonomy Terms
    1. Taxonomy terms are used to tag nodes and media so they can be classified, organized, and acted upon.  They must contain a
`field_external_uri` field that holds an external URI for the term from a controlled vocabulary / ontology.  The `islandora_core_feature`
provides a migration that can be executed to load all of the required terms for basic use into your repository.
 
The `islandora_demo_feature` provides a complete example of content modeling in Islandora for audio, video, files, and images, including
tiff and JP2 support (e.g. large images).  This includes some more advanced techniques, like switching display modes based on
taxonomy terms so 'images' and 'large images' can share a metadata profile but be displayed differently.  It also includes
example actions for generating image derivatives (using the `islandora_image` module).  You may not, however, want all of this functionality.
In fact, this feature is not meant to be the end-all-be-all of content modeling, but serves as an example of how it's done using 
Islandora.  You can take as much or as little of it as you'd like. If you're doing you're own thing, the gist is:

- When making your own content type, it will require `field_member_of`, `field_tags`, and an RDF mapping.
- When making your own media type, it will require `field_media_of`, `field_tags`, `field_mimetype`, an RDF mapping, and a field to hold the file.
You can re-use `field_media_file`, `field_media_image`, `field_media_audio`, and `field_media_video` to do so.  Media should
always be tagged (`field_tags`) with a term from the pcdmuse ontology (preservation master, service file, thumbnail image) to denote its usage.  
- When making your own taxonomy vocabulary, its terms will require `field_external_uri` and an RDF mapping.
- All RDF mappings need to map the `changed` time to `schema:dateModified`.

## Actions

Islandora provides several useful actions for repository administrators that can be configured and executed through the user
interface.  Any view can expose bulk operations by adding a `Bulk update` field to its display.

Islandora also provides a thin wrapper around Actions so that they can be used in conjunction with the `context` module.
Repository events for indexing, deletion, and derivative generation are all handled by selecting one or more preconfigured
actions using the `context` user interface. 

### Delete Media

You can use the `Delete media` action to bulk delete media, but not delete source files. 

### Delete Media and File(s)

You can use the `Delete media and file(s)` action to bulk delete media and their source files.

### Emit Node/Media/File/Term Event

You can use `Emit a * event to a queue/topic` actions to produce messages so background processes can consume them and
perform work.  The `islandora_core_feature` contains several preconfigured actions to perform indexing and removal
operations for Fedora and a triplestore.  

## REST API

Islandora has a light, mostly RESTful HTTP API that relies heavily on Drupal's core Rest module. The majority of what Islandora
provides is Link headers in GET and HEAD responses.  These headers can be used to locate related resources and navigate your
repository. In addition to these link headers, there are additional endpoints exposed for uploading files, as well as a couple
of useful REST exports.

### Exposed Headers

#### Referenced taxonomy terms (Nodes and Media)

The taxonomy terms used to tag content are exposed as link headers with `rel="tag"` and a title equal to the taxonomy term's display
label.  If the term has an external URI in a controlled vocabulary, then that URI is provided.  Otherwise, the local Drupal URI is
provided.  For example, if a piece of content is tagged with `taxonomy/term/1`, which has a display label of "Example Term", then the
link header returned in a GET or HEAD response would look like `Link: <http://example.org/taxonomy/term/1>; rel="tag"; title="Example Term"`

If instead the term were to have the `field_external_uri` field with a value of `http://purl.org/dc/dcmitype/Collection` then the link
header would look like `Link: <http://purl.org/dc/dcmitype/Collection>; rel="tag"; title="Example Term"`.   

#### Referenced entities (Nodes and Media)

Entity reference fields are exposed as link headers with `rel="related"` and a title equal to the entity reference field's display label.
For example, if `http://example.org/node/1` has an entity reference field name "Associated Content" that references 
`http://example.org/node/2`, then the link header returned in a GET or HEAD response would look like 
`Link: <http://example.org/node/2>; rel="related"; title="Associated Content"`. 

#### Associated media (Nodes only)

Media entities that belong to nodes and are tagged with terms from the PCDM Use ontology are exposed as link headers with `rel="related"`
and a title equal to the display label of the taxonomy term.  For example, if a Media is tagged as `Preservation Master` indicating
that it is the archival copy, the link header returned in a GET or HEAD response for a node would look like
`Link: <http://example.org/media/1>; rel="related"; title="Preservation Master"`.

#### Source files (Media only)

Files that are the source for Media entities are exposed as Link headers in the GET and HEAD responses with `rel="describes"`. The endpoint
to edit the contents of the source file is also exposed using `rel="edit-media"`. For example, if `http://example.org/media/1` has the source
file `http://example.org/file.txt`, then a GET or HEAD response would contain both
- `Link: <http://example.org/file.txt>; rel="describes"`
- `Link: <http://example.org/media/1/source>; rel="edit-media"`  

### Exposed Endpoints

#### /media/{media}/source

You can PUT content to the `/media/{media}/source` endpoint to update the source file for a media.  The `Content-Type`
header is required in addition to the PUT body.  Requests with empty bodies or no `Content-Type` header will be rejected.

Example usage:
```
curl -u admin:islandora -v -X PUT -H 'Content-Type: image/png' --data-binary @my_image.png localhost:8000/media/1/source
```

#### /node/{node}/media/{media_type}/{taxonomy_term}

You can PUT content to the `/node/{node}/media/{media_type}/{taxonomy_term}` endpoint to create or update Media for Nodes. Media created
in this way will automatically be assigned to the node in the route and tagged with the term in the route.  The `Content-Type`
header is expected, as well as a `Content-Disposition` header of the form `attachment; filename="your_filename"` to indicate
the name to give the file if it's new.  Requests with empty bodies or that are without `Content-Type` and `Content-Disposition`
headers will be rejected.

For example, to create a new Image media for node 1, and tag it with taxonomy term 1:
```
curl -v -u admin:islandora -H "Content-Type: image/jpeg" -H "Content-Disposition: attachment; filename=\"test.jpeg\"" --data-binary @test.jpeg http://localhost:8000/node/1/media/image/1
```

Or, to update an existing image media that is tagged with taxonomy term 2:
```
curl -v -u admin:islandora -H "Content-Type: image/jpeg" -H "Content-Disposition: attachment; filename=\"test2.jpeg\"" --data-binary @test2.jpeg http://localhost:8000/node/1/media/image/2
```

#### /node/{node}/members

You can issue GET requests to this endpoint to get a list of members of a node.  It is actually a REST export, and requires the `_format` query param.  It can (read should) also be paged
like other REST export.  For example, to get a paged list of members for a node, ten at a time:

```
curl -v -u admin:islandora http://localhost:8000/node/1/members?_format=json&items_per_page=10&offset=0 
```

#### /node/{node}/media

You can issue GET requests to this endpoint to get a list of media of a node.  It is actually a REST export, and requires the `_format` query param.  Like the members endpoint, it can 
be paged, but is less likely to be necessary as most nodes don't have that many media.  For example, to get the full list of media for a node:

```
curl -v -u admin:islandora http://localhost:8000/node/1/media?_format=json 
```

## Maintainers

Current maintainers:

* [Diego Pino](https://github.com/diegopino)
* [Jared Whiklo](https://github.com/whikloj)
* [Danny Lamb](https://github.com/dannylamb)

## Development

If you would like to contribute, please get involved by attending our weekly 
[Tech Call][4]. We love to hear from you!

If you would like to contribute code to the project, you need to be covered by 
an Islandora Foundation [Contributor License Agreement][5] or 
[Corporate Contributor License Agreement][6]. Please see the 
[Contributors][7] pages on Islandora.ca for more information.

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)

[1]: https://travis-ci.org/Islandora/islandora.png?branch=8.x-1.x
[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square
[4]: https://github.com/Islandora/documentation/wiki
[5]: http://islandora.ca/sites/default/files/islandora_cla.pdf
[6]: http://islandora.ca/sites/default/files/islandora_ccla.pdf
[7]: http://islandora.ca/resources/contributors

