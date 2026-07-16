---
name: quiqqer_secure_coding
description: Use when writing dynamic values into QUIQQER Smarty templates or building database queries. Currently covers common template XSS and SQL injection mistakes.
category: developer
---

# QUIQQER Secure Coding

This skill currently covers common XSS mistakes in Smarty templates and SQL injection risks. Additional
security topics may be added when concrete QUIQQER conventions are established.

## XSS in Smarty Templates

Dynamic values written into HTML text or quoted HTML attributes are escaped at output time:

```smarty
<input type="text" name="email" value="{$prefilledEmail|escape:'html'}">
<a title="{$Crumb->getAttribute('title')|escape:'html'}">…</a>
```

An unescaped value such as `"><script>alert(1)</script>` can break out of a quoted attribute and inject
HTML or JavaScript. When the value originates from a manipulated request URL, this can cause reflected
XSS.

Rules:

- Escape dynamic HTML text and quoted HTML attribute values with `|escape:'html'`.
- Escape at output time. Do not assume that a value is safe because it was previously cleaned or comes
  from the database.
- Do not apply HTML escaping blindly in JavaScript, JSON, CSS, or URL contexts. These contexts require
  appropriate encoding or validation.
- Output intentional HTML without escaping only when it has passed through an established and documented
  HTML sanitizing or rendering process.
- When fixing a security issue, add a regression test using the attack value that exposed the
  vulnerability.

## JavaScript DOM Output

- Use `textContent` when inserting dynamic plain text.
- Do not concatenate user- or request-derived values into strings passed to `innerHTML`.
- Treat dynamic URLs and security-relevant attributes separately. `setAttribute()` alone does not make
  their values safe.

## SQL Injection

- Never concatenate request or user-controlled values into SQL.
- Use placeholders and parameter binding with prepared statements or the Doctrine DBAL query builder.
- Using the query builder alone does not prevent SQL injection.
- SQL identifiers such as table and column names cannot be bound as parameters. Map externally supplied
  identifiers to a fixed allowlist.

## Checklist

- [ ] Dynamic HTML text and quoted attribute values use `|escape:'html'`.
- [ ] Other output contexts do not blindly use HTML escaping.
- [ ] No user-derived values are concatenated into strings passed to `innerHTML`.
- [ ] All dynamic SQL values use placeholders and parameter binding.
- [ ] Dynamic SQL identifiers are selected from a fixed allowlist.
- [ ] Security fixes include a regression test for the concrete attack.
