# Template parser: loops and conditions

Besides plain placeholders, templates can loop over a repeater and show or hide markup with conditions, in the spirit of the CodeIgniter template parser.

**Loops** repeat the body for each row of a repeater. The closing tag repeats the scope and key.

```html
<ul>
{{gp.page:features|Features|text}}
  <li>{{index}} of {{count}}: {{item}}</li>
{{/gp.page:features}}
</ul>
```

| Loop variable | Value |
| --- | --- |
| `{{item}}` | The current row, escaped for the field type |
| `{{index}}` | Row number, starting at 1 |
| `{{count}}` | Total number of rows |
| `{{first}}` / `{{last}}` | `1` on the first / last row, empty otherwise |

A variable that has a closing tag later in the template is a loop, and its field is registered as a repeater automatically. Outside a loop, `{{item}}` and friends are left untouched. An empty repeater prints nothing.

**Conditions** show a block only when the value is not empty:

```html
{{if gp:hero_image|image|home}}
  <img src="{{gp:hero_image}}" alt="">
{{else}}
  <p>No image yet</p>
{{/if}}

{{if !gp.page:subtitle|text}} ... {{/if}}
```

- `!` negates the test: `{{if !gp:key}}` runs when the value is empty.
- `{{else}}` is optional. `{{endif}}` also closes an `{{if}}`.
- Conditions can be nested.
- Inside a loop, `{{if first}}`, `{{if last}}` and `{{if !first}}` test the loop position.
- A condition registers its field too, so `{{if gp:key|image|home}}` creates the field.

**Safety rules**

- Output is never parsed again. If a saved value contains text that looks like a token, it prints as plain escaped text.
- Any `{{tag}}` that is not a Guava Pattern tag is left untouched, so other templating is not broken.
- Tokens with an invalid key (only letters, numbers, `_` and `-` are allowed) are left untouched.
