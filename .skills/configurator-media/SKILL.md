---
name: configurator-media
description: Use when the user wants to download or copy media files into the Magento media directory via the configurator. Triggers on mentions of media download, image download, file copy, media import.
---

# Media Component

- **Alias**: `media`
- **PHP class**: `CtiDigital\Configurator\Component\Media`
- **Sample**: `Samples/Components/Media/media.yaml`

## YAML Schema

The YAML structure mirrors the desired folder hierarchy under `pub/media/`. Top-level keys are folder names. Folders can be nested. Leaf nodes are arrays of file objects with `name` and `location`.

```yaml
<folder_name>:
  -
    name: <filename>
    location: <url-or-local-path>
  -
    name: <filename>
    location: <url-or-local-path>
<folder_name>:
  <subfolder_name>:
    -
      name: <filename>
      location: <url-or-local-path>
```

## Fields

| Field      | Required | Description |
|------------|----------|-------------|
| `name`     | Yes      | The filename to save the file as (e.g. `placeholder.gif`). |
| `location` | Yes      | Source URL (HTTP/HTTPS) or local file path. The file contents are fetched from this location. |

## Folder Structure

The YAML key hierarchy defines the directory structure. Non-numeric keys create directories; numeric-indexed entries (array items) are treated as files. Directories are created with 0777 permissions if they do not exist.

### Flat folder with files

```yaml
wysiwyg:
  -
    name: placeholder.gif
    location: http://placehold.it/350x150
  -
    name: placeholder2.gif
    location: http://placehold.it/350x550
```

Result:
```
pub/media/wysiwyg/placeholder.gif
pub/media/wysiwyg/placeholder2.gif
```

### Nested folders

```yaml
other_folder:
  another_sub_folder:
    -
      name: placeholder1.gif
      location: http://placehold.it/350x150
    -
      name: placeholder2.gif
      location: http://placehold.it/350x550
```

Result:
```
pub/media/other_folder/another_sub_folder/placeholder1.gif
pub/media/other_folder/another_sub_folder/placeholder2.gif
```

## Processing Behavior

- The base path is `pub/media/` (resolved via Magento's `DirectoryList::MEDIA`).
- Directories are created recursively as the YAML hierarchy is traversed.
- If a file already exists at the target path, it is **skipped** (not overwritten). The component logs a comment and moves on.
- Files are downloaded using `DriverInterface::fileGetContents()`, which supports both HTTP URLs and local file paths.
- Downloaded content is written with `DriverInterface::filePutContents()`.
- There is no checksum or modification-time comparison -- existence of the file is the only check.
