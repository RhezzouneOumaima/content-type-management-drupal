With the Radioactivity module you can measure popularity of your content. In
combination with Views you can makes lists of popular content.

INSTALLATION
============

Install and enable the module as usual:
https://www.drupal.org/docs/extending-drupal/installing-modules

The Radioactivity module has no dependencies other than Drupal core.

CONFIGURATION
=============

The Radioactivity module provides two field types: Radioactivity and
Radioactivity Reference.

Radioactivity reference field
  The Radioactivity Reference field stores the energy value in a separate
  entity. It is the new kid on the block, it was introduced in the 4.0.0
  release. It was developed to mitigate the above editorial problem. Although it
  is technically an entity reference field, the field itself it manages the
  referenced radioactivity entity and only exposes the energy level to the
  editor.

  When you add a Radioactivity reference field to a node type, which already has
  content in the database, some additional steps are required. After adding a
  new field, the content for these field, Radioactivity entities, needs to be
  created retroactively. A drush command is provided for this purpose:

    drush radioactivity:fix-references

  For more information, see the on-line documentation:
     https://www.drupal.org/docs/contributed-modules/radioactivity

Radioactivity field (deprecated)
  The Radioactivity field stores the energy value as part of the entity to which
  the field is added. This is the classic approach. It is technically proven but
  has a known problem which prevents editors for saving the node when cron
  updates the energy values while the node is being edited.
  See https://www.drupal.org/project/radioactivity/issues/3106687

  NOTE that this field is only available when your site is already using it. On
  new sites it is not listed in the select list when you add a field to an
  entity. It is only available for legacy purposes.

  The Radioactivity field was deprecated in 4.0.0 and will be removed in the
  5.0.0 release. You are encouraged to use the Radioactivity Reference field
  (above) instead.

Field formatters

  The Radioactivity fields come with two formatters: 'Emitter' and 'Value'.
  Use the Emitter formatter only on those places where a view should be counted
  (e.g. Full content) and not in lists of content that should not be measured
  for popularity. Each instance of the Emitter formatter can be configured with
  its own Energy value to emit. E.g. a Full content view can emit more energy
  than a search result view. Use the Value formatter to only display the
  popularity, e.g. in a backend view without emitting energy (and thus not count
  for popularity).

FIELD CONFIGURATION
===================

Each Radioactivity field has storage settings that determine how the energy
value is treated and how it behaves over time.

Energy profile
  - Count: Each visit increases the energy value by one. Never decreases.
    Use this profile to count visits.
  - Linear: Energy increases by the emission amount. Decreases the energy value
    by 1 per second.
  - Decay: Energy increases by the emission amount. Decreases the energy value
    by 50% per half-life time (see below).

Granularity
  The minimum time in seconds before energy decay will be re-calculated. Cron
  should run at least one time within this time frame. If for example you want
  the popularity list to be updated daily, the granularity should be 3600 * 24
  seconds or less. A lower Granularity spreads the load of processing decays.
  Set this time to a (very) low value to update on each cron.

Half-life time
  The time in seconds in which the energy decreases by 50%. Start with a high
  value (e.g. 1 week or longer). Create a view in the backend to monitor the
  result and monitor the behaviour over time. Decrease the half-life time to
  better distinguish between old and new. Increase the half-life time if not
  enough nodes have an energy value.

Cutoff
  Energy levels under this value is set to zero. Example: 0.5, 2.
  This value is used limit the number of entities that must be processed.
  Energy values below this threshold are considered to be not relevant for
  popularity. Start with a low value (e.g. 1/10th of a single emission) and
  increase the value to reduce the number of items. For example, you have list
  of the 10 most popular nodes. Set the cut-off value equal to the average
  energy of the item on position 20.

Default energy (field setting)
  The initial energy value when content is created. Use this value to give new
  content an initial (high) ranking in the popularity list. Start with 10 times
  the energy of a single emission (see below) and adjust the value to
  balance new and popular content within the list of popular items.

Energy (Emitter formatter)
  The amount of energy to emit when this field is displayed. Each time the
  content is displayed this value will be added to the energy level. Use a
  simple value such as 1 or 10. On a busy site use 1 on a low traffic site use
  10. If you count popularity using different view modes (e.g. Full content and
  Search result) you can use distinct Energy values to rate each view mode
  differently (e.g. Full content: 10, Search result: 1).

CRON
====

The Radioactivity module uses cron to update the energy values. Only after a
cron job you will see the energy level change. Emissions are processed (energy
increases) and decay is processed (energy decreases). Emissions are processed
on each cron job. Decay is processed after the Granularity time has elapsed.

MULTILINGUAL
============

Only the Radioactivity Reference field supports multilingual. Enable field
translation when you want to differentiate the content popularity per language.
Do not enable the field translation to use the same popularity value for each
language.

VIEWS INTEGRATION
=================

Content popularity is typically used to show a list of popular content (either
in the front or in the backend). To create a view of popular nodes:
- Create a list view of nodes
- Add a relationship 'Content using field_name', and require the relationship.
- Sort by descending Energy (Relation: Radioactivity field)

STORAGE
=======

Radioactivity module provides two storages to store the emitted incident data:
in the database (default) or in a file. The Default Storage uses the
'radioactivity_incident' table in Drupal's database. The Rest Storage uses a
file in the server's temporary file directory.

The storage configuration can be altered in settings.php. For the the default
storage, you can skip this section.

```
// Use the radioactivity Default Storage (database).
$config['radioactivity.storage']['type'] = 'default';
```

```
// Use the radioactivity Rest Storage (file).
$config['radioactivity.storage']['type'] = 'rest_local';
```

The provided rest storage service uses a php executable as endpoint. The
default endpoint is: /path/to/radioactivity/endpoints/file/rest.php. If you
want to run the service under another path (e.g. directly under the root) or on
a different host, you can override the endpoint url:

```
// Use the radioactivity Rest Storage (file) on a custom endpoint.
$config['radioactivity.storage'] = [
    'type' => 'rest_remote',
    'endpoint' => 'http://www.example.com/rest.php',
];
```

NOTE! When using a different host name you may need to set CORS (Cross-origin
resource sharing) for things to work properly.

DEBUGGING
=========

When the energy value does not increase or decrease as you expect, use the
hints below to check.

- In the view mode configuration of your content type
  (/admin/structure/types/manage/{node type}/display) select the 'Emitter'
  formatter and check the 'Display current energy value' setting. Save the view
  mode and the field will now display the current energy value.
- Edit the node and check the Energy value in the field. The value is the same
  as the displayed value in the hint above.
- When a page is visited a POST request should be fired to
	/radioactivity/emit (default storage) or
 	/path/to/radioactivity/endpoints/file/rest.php (local rest storage)
- Emissions can be found in the database (default storage) with
  `SELECT * FROM radioactivity_incident` or in the temporary storage directory
  at /tmp/radioactivity-payload.json (assuming the directory is /tmp).
- Cron reports the Radioactivity processing results. Example
  ```
  drush cron
   ...
   [notice] Processed 2 radioactivity decays.
   [notice] Processed 1 radioactivity incidents.
  ```
  The numbers refer to the number of entities processed, not the individual
  incidents. Unpublished entities also gets counted, but their data is
  not processed (no decay).
  Set the Granularity to 1 to make sure each cron job will update the energy.
