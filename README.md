Pdf Toc (module upgraded for Omeka S)
=============================


Summary
-----------

Omeka's module that add table of contents metadata to a pdf media.
That allow index searching within [Universal Viewer](https://github.com/Daniel-KM/Omeka-S-module-UniversalViewer) module for omeka S with [IIIF-Server](https://github.com/bubdxm/Omeka-S-module-IiifServer)

Installation
------------
- This module needs pdftk command-line tool on your server

```
    sudo apt-get install poppler-utils
```

- you can install the module via github

```
    cd omeka-s/modules  
    git clone git@github.com:bubdxm/Omeka-S-module-PdfToc.git "PdfToc"
```

- Install it from the admin → Modules → PdfToc -> install

Using the Pdf Toc module
---------------------------

- Create an item
- Add PDF file(s) to this item
- Save Item
- If you go to the media's view you should see dcterms:tableofcontents filled. 


Optional plugins
----------------

- [Universal Viewer](https://github.com/Daniel-KM/Omeka-S-module-UniversalViewer) : Module for Omeka S that adds the IIIF specifications in order to act like an IIPImage server, and the UniversalViewer, a unified online player for any file. It can display books, images, maps, audio, movies, pdf, 3D views, and anything else as long as the appropriate extensions are installed.

- [IIIF-Server](https://github.com/bubdxm/Omeka-S-module-IiifServer) : Module for Omeka S that adds the IIIF specifications to serve any images and medias. 
**Here, permit to add the table of contents if you display pdf's images in unviersal viewer  **

Troubleshooting
---------------

See online [Pdf Toc issues](https://github.com/bubdxm/Omeka-S-module-PdfToc/issues).


License
-------

This module is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

* Syvain Machefert, Université Bordeaux 3 (see [symac](https://github.com/symac))




