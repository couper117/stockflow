import coreWebVitals from 'eslint-config-next/core-web-vitals';
import nextTypescript from 'eslint-config-next/typescript';
import prettier from 'eslint-config-prettier';

// eslint-config-next 16 ships native flat configs, so we spread them directly
// (no FlatCompat). Order: Next rules → disable formatting rules (prettier) →
// our project guards.
const config = [
  ...coreWebVitals,
  ...nextTypescript,
  prettier,
  {
    rules: {
      // (a) No template-literal SQL — parameterised queries only (SF-DOC NFR-SEC-06).
      //     Drizzle's sql`` tag is the sanctioned exception; this catches raw
      //     string-built queries passed to db.query/execute/unsafe.
      // (b) No dangerouslySetInnerHTML (XSS).
      // (c) No direct process.env outside src/lib/env.ts.
      'no-restricted-syntax': [
        'error',
        {
          selector:
            "CallExpression[callee.property.name=/^(query|execute|unsafe)$/] > TemplateLiteral",
          message:
            'No template-literal SQL. Use the Drizzle query builder or the parameterised sql`` tag.',
        },
        {
          selector: "JSXAttribute[name.name='dangerouslySetInnerHTML']",
          message: 'dangerouslySetInnerHTML is banned (XSS). Render text, not HTML.',
        },
        {
          selector: "MemberExpression[object.name='process'][property.name='env']",
          message: 'Do not read process.env directly. Import the validated config from @/lib/env.',
        },
      ],
    },
  },
  {
    // The config module and out-of-Next tooling are the only places allowed to read process.env.
    files: ['src/lib/env.ts', 'drizzle.config.ts', 'scripts/**', 'vitest.config.ts'],
    rules: { 'no-restricted-syntax': 'off' },
  },
  { ignores: ['.next/**', 'node_modules/**', 'drizzle/**', 'coverage/**', 'legacy/**'] },
];

export default config;
