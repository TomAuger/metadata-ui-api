WordPress Metadata UI API
=======================

An API for building form UI for WordPress content types (post types, users, comments, Settings options, etc.). The current focus is **post types**, with the overall goal of a uniform API to work with across all objects to add fields to forms.

This is a project of the [WordPress core Options/Metadata team](http://make.wordpress.org/core/components/options-meta/).

## Documentation

We are preparing to start documenting things now that we have our initial base code, check back soon!

Want to help? Check the Contributing section below for more details.

## Example:

```php
	/**
	 * Example init of fields
	 */
	function example_init()  {

		register_post_type( 'pm_solution',  array(
			'label'   =>  __( 'Solutions',  'pm-sherpa' ),
			'public'  =>  true,
			'rewrite' =>  true,
			'form'    =>  'after-title'
		) );

		register_post_field( 'website', 'pm_solution',  array(
			'type'              =>  'url',
			'label'             =>  __( 'Website',  'pm-sherpa' ),
			'html_placeholder'  =>  'http://www.example.com',
			'html_size'         =>  50
		) );

		register_post_field( 'tagline', 'pm_solution',  array(
			'label'     =>  __( 'Tagline',  'pm-sherpa' ),
			'html_size' =>  50
		) );

		register_post_field( 'blurb', 'pm_solution',  array(
			'type'      =>  'textarea',
			'label'     =>  __( 'Blurb',  'pm-sherpa' ),
			'html_size' =>  160
		) );

	}
	add_action( 'init', 'example_init' );
```

## Contributing

We welcome contributions. That being said, be aware that any functionality that is missing, we're probably already aware of. Take a look through existing issues, and feel free to open up a new one to discuss the changes you'd like to make. After discussion in an issue we'll be happy to review a pull request. Anyone can be a part of this, if you need help, just ask!

Join us in #wordpress-core-plugins on Freenode IRC and seek out sc0ttkclark or mikeschinkel if you need **anything** to help you get started, or open up an issue here with your questions/feedback.

### Where we need help most

* **Field Types** - We need help building new field types, we currently have a few basic fields to start off with including the Text, Textarea, URL, Date, and Hidden field types.
* **Actions / Filters** - We are now seeking to add in the hooks necessary to extend things further, feel free to request hooks anywhere you see a need for them.
* **Repeatable Fields** - We are architecting a solution for repeatable fields, in which you can take a single field and make it have repeatable inputs that let someone add multiple values. These values would be stored in multiple meta values for the same meta key for the specific object ID.

Have any other ideas you'd like to help out with? Hop in, the world is your oyster and we're eager to help you get those pearls!

## DISCLAIMER

This software is in alpha until otherwise noted. There is no guarantee on backwards compatibility nor a warrantee. It is not recommended to be used on any production site.

## LICENSE

GPLv2 or later. See [License](LICENSE.txt).
