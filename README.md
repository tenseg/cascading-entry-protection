# Cascading Entry Protection

Cascading Entry Protection is a Statamic addon that provides a entry protection method that cascades from parent entries to child entries on collections that have a hierarchy (or "orderable" collections).

## Features

Cascading Entry Protection provides:

- A settings page where you can determine which collections will use this protection method, how long access tickets will last, and define access tickets for your site;
- Each access ticket may include one or more passwords that will grant users access to protected entries;
- Any entry may assign any number of access tickets to provide protection;
- Any child of an entry with access tickets assigned will enjoy the same protection automatically;
- A fieldset is available to add required fields to a collection's blueprint.
- If a collection is mounted on an entry, then the whole collection will be protected if the mount-point entry is protected. In other words, the mount-point page is treated as a "parent" of all the pages in the mounted collection.

Using this addon does not prevent you using the other protection schemes provided by Statamic. If an entry is protected by a different Statamic protection scheme, that will take precedence over the Cascading Entry Protection scheme. But do note that the regular Statamic protection schemes do not cascade to child entries.

## How to Install

You can install this addon via Composer:

``` bash
composer require tenseg/cascading-entry-protection
```

## How to Use

Go to the addon settings for Cascading Entry Protection, select the collections to protect, and create at least one ticket with passwords.

Add the neccessary fields to the collection's blueprint, usually by linking the Cascading Entry Protection fieldset to a new section of fields in the entry's sidebar area. However, you can add the necessary fields yourself manually if you like. Just look over the provided fieldset to see what you will need.

Then edit an entry in that collection and choose one of the tickets you defined in the settings to apply to that entry and it's children.

## Details

When a user supplies a password that matches one of the passwords on a ticket protecting an entry, then a cookie is placed on their browser that records that password and automatically supplies it for a period of time you define in the settings. Since different pages may require different passwords, this cookie will record all the supplied passwords for future reference.

If you ever remove or change a password for a given ticket, then any users who used that old password will stop having access to the entries protected by that ticket. Of course, if they know one of the remaining passwords then they can just type that one in and it will be stored.

Since the passwords are assigned to tickets and the tickets are assigned to entries, you can change the passwords in one place, with no need to go visit every entry to make recurring changes. Depending on your needs, your website may require only a single ticket with a single password, a single ticket with many passwords, or many tickets with different passwords. You can choose how complex you need your site's authorization to be.

But do keep in mind that these are all "shared passwords" that you will hand out to a number of users. This is a very loose form of protection. Think of it as a thin curtain over your content. It is not robust security by any means, just a convenient way to screen some content for certain privilaged visitors to your website.

Also note that any protected page will be effectively blocked from search engines and will not get indexed.

## Caching Issues

Please note that we have not yet resolved all [caching issues](https://github.com/tenseg/cascading-entry-protection/issues/1) with this addon. It is possible that when editing the restrictions, some cached instances of child entries will not be flushed and refreshed. For now, please be sure to manually flush caches when making changes to restrictions.

## License

The code unique to this addon is licensed by Tenseg LLC under the MIT License. Please see the [License File](https://github.com/tenseg/cascading-entry-protection/blob/main/LICENSE) for information. Statamic itself is commercial software and has [its own license](https://statamic.com/license).
