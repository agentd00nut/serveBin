# serveBin

Simple Slim3 based server that blindly accepts posts and makes bins with
your configured user account to pasteBin.com


# Starting Server

Fill out cache/userOptions with your credentials for pasteBin.

Run the server.
` composer start`
or
`php -S localhost:8080 -t public`
or
Serve up the public folder with your favorite web server.


# POST

`curl -F upload=@- http://localhost:8080/`

or 

`'curl -F upload=@- -F private=1 http://localhost:8080/'` to change the 
privacy level of the bin.

You'll get back either an error message, or a link to the pasteBin that
was created.

# GET

You can also retrieve pastes directly from pasteBin... Though this server
will only try to render pastes that are 1:1 copies of an image!

`http://localhost:8080/cuSiKL1V`

That's probably a security flaw?  Depends how much you can trust `htmlspecialchars`

# Security

There isn't much.

Leave the secretMessage blank if you want anyone who can post at your
server to be able to post message as you.

Obviously if this is going to be hosted anywhere public that's likely to
be a poor choice.

Also obviously this is a terrible way to do security and someone who wants
to write something more appropriate is free to open a pull request :)


# Upgrades
Many.

Security.
Handling all the options from the $_POST variable would be neat.
Cleaning up the class.
Cleaning up how requests to paste bin are handled.
Add real error handling.

I will likely do none of those things so feel free!

