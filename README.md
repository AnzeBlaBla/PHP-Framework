# PHP Framework

This is a PHP framework that is just a fun little project so I have something to do. It is not meant to be used in any way, as it's very bad.

## Functionality

The framework is very simple. The basic idea is that every component is it's own file, then you can reuse components with different props (similar to React).

Inside the components you mostly use the helper object provided to do things like add functionality to buttons. You can also store data directly in `$this->data` and it will be auto-saved to the session.

There's also a built-in connection to the DB. You can use it by calling `$this->db->query()` or `$this->db->queryOne()` or `$this->db->execute()`.

Using this feels like a shitty version of React where you have to mess around with PHP's general shittiness. Good luck ;)

## Goal

My goal was to create a tool where all the logic (frontend and backend) + styles could be in one file (similar to Next.js).

## Todo

- [ ] File system routing
  - [ ] Layouts
  - [ ] Rewrites and redirects
- [ ] Solve problem where data is kept even when you don't want it (duh, everything is stored in session) - for example when a component is rerendered
- [ ] Only rerender components that have changed
