[//]: # (TITLE: Rethinking Form Development)
[//]: # (DATE: 2000-00-00T00:00:00+01:00)
[//]: # (TAGS: Symfony, Forms, DTO, Data Transfer Object, Entity)

In one of my previous blog posts, [Avoiding Entities in Forms][entities in forms], I've shown how to decouple your forms
from your entities. Afterwards I got a lot of feedback and most of it was about the lack of examples and the flow, when
to fill your data and how to get this back in the entity. One thing I notice however, is that developers design forms
based on their entities. This leads to complex forms because you're confined to a strict set of properties. Developers
often enough get struck with unmapped fields and form events to work their way around those limitations.

With Symfony Forms I highly encourage to follow the [composition over inheritance][composition over inheritance]
principle. Small form types are easier to re-use and make it less complex to build forms. Moreover, this allows small
data objects to have specific goals with validation for their specific case, rather than complex validation groups.

## First part no longer in the intro

[entities in forms]: /post/avoiding-entities-in-forms
[composition over inheritance]: https://en.wikipedia.org/wiki/Composition_over_inheritance
