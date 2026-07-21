# types

Shared type definitions for the app.

The frontend is **JavaScript, not TypeScript** (see `CLAUDE.md` §2), so shared
shapes are documented as [JSDoc `@typedef`](https://jsdoc.app/tags-typedef.html)
declarations here and imported where needed:

```js
/** @typedef {import('@/types').ApiResponse} ApiResponse */
```

Keep only cross-feature shapes here (e.g. the API envelope, the authenticated
user). Feature-local shapes belong with their feature module.
