# Webling API Documentation

**Version:** 1.0  
**Base URL:** `https://{domain}.webling.ch/api/1/`

## Overview
REST API for interacting with Webling database - member management, finance, documents, and more

## Authentication
**Type:** API Key  
**Description:** Authentication is done by passing an API-Key. As an Administrator you can generate your API-Key in the Web App (Administration > API).

**Methods:**
| Method            | Format Example                      |
|-------------------|-------------------------------------|
| Query Parameter   | `/api/1/member?apikey={your_api_key}` |
| Header            | `apikey: {your_api_key}`            |

## Endpoints

### Member Endpoints

#### `GET /member`
**Description:** Lists all available Member IDs  
**Parameters:**
| Name     | Type   | Required | Description                      | Example       |
|----------|--------|----------|----------------------------------|---------------|
| filter   | string | No       | Filter using Query Language      |               |
| order    | string | No       | Sort by property and direction   | `Name ASC`    |
| format   | string | No       | Return full objects              | `format=full` |

**Response:**
```json
{
  "objects": [123, 456]
}
```

#### `POST /member`
**Description:** Create a member  
**Request Schema:**
```json
{
  "type": "member",
  "properties": {"Vorname": "Max", "Name": "Muster"},
  "parents": [789],
  "links": {"debitors": [101112]}
}
```
**Response:** `201 Created` with new member ID

#### `GET /member/{id}`
**Description:** Get a member  
**Parameters:**
| Name | Type   | Required | Description  |
|------|--------|----------|--------------|
| id   | number | Yes      | Member ID    |

**Response Schema:**
```json
{
  "type": "member",
  "readonly": false,
  "properties": {"Name": "Muster"},
  "parents": [789],
  "links": {}
}
```

### Financial Endpoints

#### `GET /debitor`
**Description:** Lists all debitor IDs  
**Parameters:**
| Name     | Type   | Required | Description                      |
|----------|--------|----------|----------------------------------|
| filter   | string | No       | Filter using Query Language      |
| format   | string | No       | Return full objects              |

**Response:**
```json
{
  "objects": [4455, 6677]
}
```

#### `POST /debitor`
**Description:** Create debitor with entries  
**Request Schema:**
```json
{
  "properties": {
    "title": "Invoice 2023",
    "date": "2023-12-31"
  },
  "links": {
    "revenue": [{
      "properties": {
        "amount": 150.00,
        "title": "Membership Fee"
      }
    }]
  }
}
```

#### `GET /account`
**Description:** List financial account IDs  
**Parameters:**
| Name     | Type   | Required | Description        |
|----------|--------|----------|--------------------|
| order    | string | No       | Sort order         |

**Response:**
```json
{
  "objects": [1122, 3344]
}
```

### Document Management

#### `GET /document`
**Description:** List document IDs  
**Parameters:**
| Name     | Type   | Required | Description        |
|----------|--------|----------|--------------------|
| format   | string | No       | Return full objects|

**Response:**
```json
{
  "objects": [5566, 7788]
}
```

## Query Language
**Operators:**
| Operator | Description                  | Example                      |
|----------|------------------------------|------------------------------|
| =        | Equal                        | `Name` = "Meier"             |
| !=       | Not equal                    | `Status` != "Inactive"       |
| <        | Less than                    | `Value` < 1000               |
| FILTER   | Starts with                  | `Name` FILTER "Me"           |
| CONTAINS | Contains substring           | `Address` CONTAINS "Street"  |
| IN       | Matches list                 | `Type` IN ("A", "B")         |
| WITH     | Cross-object query           | WITH $links.debitor(totalamount > 100) |

**Special Properties:**
- `$parents.<property>`: Parent object properties
- `$children.<type>.<property>`: Child object properties
- `$links.<category>.<property>`: Linked object properties
- `$readonly`: Read-only status (true/false)
- `$writable`: Write permission status
- `$id`: Object ID
- `$label`: Human-readable label

**Functions:**
- `LOWER(string)`: Convert to lowercase
- `YEAR(date)`: Extract year
- `TODAY()`: Current date

## Response Codes
| Code | Description                               |
|------|-------------------------------------------|
| 200  | OK - Successful request                   |
| 201  | Created - Resource created                |
| 204  | No Content - Successful empty response    |
| 304  | Not Modified - Cached version still valid |
| 400  | Bad Request - Invalid parameters          |
| 401  | Unauthorized - Authentication failed      |
| 403  | Forbidden - Insufficient permissions      |
| 404  | Not Found - Resource unavailable          |
| 413  | Request Too Large - Split your request    |
| 425  | Quota Exceeded - Subscription limit       |
| 429  | Too Many Requests - Rate limit exceeded   |
| 500  | Internal Server Error                     |
| 503  | Service Unavailable - Try again later     |

## Rate Limits & Pagination

### Rate Limits
- **500 requests/minute** maximum
- Recommended: stay below 50 requests/minute
- HTTP 429 response indicates limit exceeded

### Pagination
**Parameters:**
| Parameter | Type   | Description          | Example     |
|-----------|--------|----------------------|-------------|
| page      | number | Page number (1-based)| page=2      |
| per_page  | number | Items per page       | per_page=50 |

**Usage:**
`GET /member?page=2&per_page=100`

## Examples

### PHP Example
```php
<?php
$apiurl = 'https://demo.webling.ch';
$apikey = '<your_api_key>';
// API call implementation
?>
```

### JavaScript Example
```javascript
$.ajax(apiurl + '/api/1/membergroup/1?apikey=' + apikey)
  .then(function(data) {
    // Handle response
  });
```

## Full documentation continues with all endpoints and details...
```json
{
  "name": "Webling API",
  "description": "REST API for interacting with Webling database - member management, finance, documents, and more",
  "version": "1.0",
  "baseUrl": "https://{domain}.webling.ch/api/1/",
  "authentication": {
    "type": "API Key",
    "description": "Authentication is done by passing an API-Key. As an Administrator you can generate your API-Key in the Web App (Administration > API).",
    "methods": [
      {
        "name": "Query Parameter",
        "format": "/api/1/member?apikey={your_api_key}"
      },
      {
        "name": "Header",
        "format": "apikey: {your_api_key}"
      }
    ]
  },
  "endpoints": [
    {
      "path": "/member",
      "method": "GET",
      "description": "Lists all available Member IDs",
      "parameters": [
        {
          "name": "filter",
          "type": "string",
          "required": false,
          "description": "Filter the list using the Query Language"
        },
        {
          "name": "order",
          "type": "string",
          "required": false,
          "description": "Sort the member list by property and direction",
          "example": "Name ASC"
        },
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Specify 'format=full' to get the full object instead of a list of IDs"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "array",
            "description": "Array of member IDs",
            "items": {
              "type": "number"
            }
          }
        }
      }
    },
    {
      "path": "/member",
      "method": "POST",
      "description": "Create a member. Only 'properties', 'parents' and 'links' can be set.",
      "request": {
        "type": "object",
        "properties": {
          "type": {
            "type": "string",
            "enum": ["member"]
          },
          "properties": {
            "type": "object",
            "description": "Member data fields like Vorname, Name, E-Mail, etc."
          },
          "parents": {
            "type": "array",
            "description": "Array of parent membergroup IDs",
            "items": {
              "type": "number"
            }
          },
          "links": {
            "type": "object",
            "description": "Linked objects like debitors"
          }
        }
      },
      "response": {
        "type": "number",
        "description": "ID of the newly created member"
      }
    },
    {
      "path": "/member/{id}",
      "method": "GET",
      "description": "Get a member",
      "parameters": [
        {
          "name": "id",
          "type": "number",
          "required": true,
          "description": "Member ID"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "type": {
            "type": "string",
            "enum": ["member"]
          },
          "readonly": {
            "type": "boolean"
          },
          "properties": {
            "type": "object",
            "description": "Member data fields"
          },
          "children": {
            "type": "object"
          },
          "parents": {
            "type": "array",
            "items": {
              "type": "number"
            }
          },
          "links": {
            "type": "object"
          }
        }
      }
    },
    {
      "path": "/member/{id}",
      "method": "PUT",
      "description": "Update a member. Only 'properties', 'parents' and 'links' may be changed.",
      "parameters": [
        {
          "name": "id",
          "type": "number",
          "required": true,
          "description": "Member ID"
        }
      ],
      "request": {
        "type": "object",
        "properties": {
          "properties": {
            "type": "object",
            "description": "Member data fields to update"
          },
          "parents": {
            "type": "array",
            "description": "Array of parent membergroup IDs"
          },
          "links": {
            "type": "object",
            "description": "Linked objects to update"
          }
        }
      }
    },
    {
      "path": "/member/{id}",
      "method": "DELETE",
      "description": "Delete a member",
      "parameters": [
        {
          "name": "id",
          "type": "number",
          "required": true,
          "description": "Member ID"
        }
      ]
    },
    {
      "path": "/membergroup",
      "method": "GET",
      "description": "Lists all available membergroup IDs. Object 'roots' lists the IDs of all root membergroups.",
      "parameters": [
        {
          "name": "filter",
          "type": "string",
          "required": false,
          "description": "Filter the list using the Query Language"
        },
        {
          "name": "order",
          "type": "string",
          "required": false,
          "description": "Sort the membergroup list by property and direction"
        },
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Specify 'format=full' to get the full object instead of a list of IDs"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "array",
            "description": "Array of membergroup IDs",
            "items": {
              "type": "number"
            }
          },
          "roots": {
            "type": "array",
            "description": "Array of root membergroup IDs",
            "items": {
              "type": "number"
            }
          }
        }
      }
    },
    {
      "path": "/debitor",
      "method": "GET",
      "description": "Lists all available debitor IDs",
      "parameters": [
        {
          "name": "filter",
          "type": "string",
          "required": false,
          "description": "Filter the list using the Query Language"
        },
        {
          "name": "order",
          "type": "string",
          "required": false,
          "description": "Sort the debitor list by property and direction"
        },
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Specify 'format=full' to get the full object instead of a list of IDs"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "array",
            "description": "Array of debitor IDs",
            "items": {
              "type": "number"
            }
          }
        }
      }
    },
    {
      "path": "/debitor",
      "method": "POST",
      "description": "Create a debitor. You always need to create an entry and attach it to the debitor within the same request.",
      "request": {
        "type": "object",
        "properties": {
          "properties": {
            "type": "object",
            "properties": {
              "title": {
                "type": "string",
                "description": "Invoice title"
              },
              "date": {
                "type": "string",
                "format": "date",
                "description": "Invoice date"
              },
              "duedate": {
                "type": "string",
                "format": "date",
                "description": "Due date"
              }
            }
          },
          "parents": {
            "type": "array",
            "description": "Array of parent period IDs",
            "items": {
              "type": "number"
            }
          },
          "links": {
            "type": "object",
            "properties": {
              "revenue": {
                "type": "array",
                "description": "Array of revenue entries",
                "items": {
                  "type": "object",
                  "properties": {
                    "properties": {
                      "type": "object",
                      "properties": {
                        "amount": {
                          "type": "number",
                          "description": "Entry amount"
                        },
                        "title": {
                          "type": "string",
                          "description": "Entry title"
                        },
                        "receipt": {
                          "type": "string",
                          "description": "Receipt number"
                        }
                      }
                    },
                    "parents": {
                      "type": "array",
                      "description": "Array of parent entrygroup IDs"
                    },
                    "links": {
                      "type": "object",
                      "properties": {
                        "credit": {
                          "type": "array",
                          "description": "Credit account IDs",
                          "items": {
                            "type": "number"
                          }
                        },
                        "debit": {
                          "type": "array",
                          "description": "Debit account IDs",
                          "items": {
                            "type": "number"
                          }
                        }
                      }
                    }
                  }
                }
              },
              "member": {
                "type": "array",
                "description": "Array of linked member IDs",
                "items": {
                  "type": "number"
                }
              }
            }
          }
        }
      },
      "response": {
        "type": "number",
        "description": "ID of the newly created debitor"
      }
    },
    {
      "path": "/account",
      "method": "GET",
      "description": "Lists all available account IDs",
      "parameters": [
        {
          "name": "filter",
          "type": "string",
          "required": false,
          "description": "Filter the list using the Query Language"
        },
        {
          "name": "order",
          "type": "string",
          "required": false,
          "description": "Sort the account list by property and direction"
        },
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Specify 'format=full' to get the full object instead of a list of IDs"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "array",
            "description": "Array of account IDs",
            "items": {
              "type": "number"
            }
          }
        }
      }
    },
    {
      "path": "/entry",
      "method": "GET",
      "description": "Lists all available entry IDs",
      "parameters": [
        {
          "name": "filter",
          "type": "string",
          "required": false,
          "description": "Filter the list using the Query Language"
        },
        {
          "name": "order",
          "type": "string",
          "required": false,
          "description": "Sort the entry list by property and direction"
        },
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Specify 'format=full' to get the full object instead of a list of IDs"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "array",
            "description": "Array of entry IDs",
            "items": {
              "type": "number"
            }
          }
        }
      }
    },
    {
      "path": "/document",
      "method": "GET",
      "description": "Lists all available document IDs",
      "parameters": [
        {
          "name": "filter",
          "type": "string",
          "required": false,
          "description": "Filter the list using the Query Language"
        },
        {
          "name": "order",
          "type": "string",
          "required": false,
          "description": "Sort the document list by property and direction"
        },
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Specify 'format=full' to get the full object instead of a list of IDs"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "array",
            "description": "Array of document IDs",
            "items": {
              "type": "number"
            }
          }
        }
      }
    },
    {
      "path": "/config",
      "method": "GET",
      "description": "Returns the config values for the current webling store",
      "response": {
        "type": "object",
        "properties": {
          "name": {
            "type": "string",
            "description": "Store name"
          },
          "domain": {
            "type": "string",
            "description": "Store domain"
          },
          "language": {
            "type": "string",
            "description": "Store language"
          },
          "userImageEnabled": {
            "type": "boolean",
            "description": "Whether user images are enabled"
          },
          "apiEnabled": {
            "type": "boolean",
            "description": "Whether API is enabled"
          }
        }
      }
    },
    {
      "path": "/quota",
      "method": "GET",
      "description": "Returns current and available quota for the current webling store",
      "response": {
        "type": "object",
        "properties": {
          "members": {
            "type": "object",
            "properties": {
              "used": {
                "type": "number",
                "description": "Number of members used"
              },
              "max": {
                "type": "number",
                "description": "Maximum number of members allowed"
              }
            }
          },
          "entries": {
            "type": "object",
            "properties": {
              "used": {
                "type": "number",
                "description": "Number of entries used"
              },
              "max": {
                "type": "number",
                "description": "Maximum number of entries allowed"
              }
            }
          },
          "storage": {
            "type": "object",
            "properties": {
              "used": {
                "type": "number",
                "description": "Storage used in bytes"
              },
              "max": {
                "type": "number",
                "description": "Maximum storage allowed in bytes"
              }
            }
          }
        }
      }
    },
    {
      "path": "/definition",
      "method": "GET",
      "description": "Returns the field configuration of all objects",
      "parameters": [
        {
          "name": "format",
          "type": "string",
          "required": false,
          "description": "Format of the definition (simple, full, zapier)"
        }
      ],
      "response": {
        "type": "object",
        "description": "Object definitions with field configurations"
      }
    },
    {
      "path": "/currentuser",
      "method": "GET",
      "description": "Returns id and name of the currently logged-in user or apikey",
      "response": {
        "type": "object",
        "properties": {
          "id": {
            "type": "number",
            "description": "User or API key ID"
          },
          "name": {
            "type": "string",
            "description": "User or API key name"
          }
        }
      }
    },
    {
      "path": "/changes/{timestamp}",
      "method": "GET",
      "description": "Get all changed objects since the passed timestamp",
      "parameters": [
        {
          "name": "timestamp",
          "type": "number",
          "required": true,
          "description": "Unix timestamp since when you want to synchronize"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "object",
            "description": "Changed object IDs grouped by type"
          },
          "deleted": {
            "type": "array",
            "description": "Array of deleted object IDs",
            "items": {
              "type": "number"
            }
          },
          "revision": {
            "type": "number",
            "description": "Latest revision number"
          },
          "version": {
            "type": "number",
            "description": "Current Webling version"
          }
        }
      }
    },
    {
      "path": "/replicate/{id}",
      "method": "GET",
      "description": "Get all changed objects since a specific revision",
      "parameters": [
        {
          "name": "id",
          "type": "number",
          "required": true,
          "description": "Revision ID"
        }
      ],
      "response": {
        "type": "object",
        "properties": {
          "objects": {
            "type": "object",
            "description": "Changed object IDs grouped by type"
          },
          "deleted": {
            "type": "array",
            "description": "Array of deleted object IDs",
            "items": {
              "type": "number"
            }
          },
          "revision": {
            "type": "number",
            "description": "Latest revision number"
          },
          "version": {
            "type": "number",
            "description": "Current Webling version"
          }
        }
      }
    }
  ],
  "queryLanguage": {
    "description": "All GET endpoints which return a list of object IDs can be filtered using a query language",
    "operators": [
      {
        "operator": "=",
        "description": "Equal",
        "example": "`Name` = \"Meier\""
      },
      {
        "operator": "!=",
        "description": "Not equal",
        "example": "`Name` != \"Meier\""
      },
      {
        "operator": "<",
        "description": "Less than",
        "example": "`PLZ` < 2000"
      },
      {
        "operator": "<=",
        "description": "Less or equal than",
        "example": "`PLZ` <= 2000"
      },
      {
        "operator": ">",
        "description": "Greater than",
        "example": "`PLZ` > 2000"
      },
      {
        "operator": ">=",
        "description": "Greater or equal than",
        "example": "`PLZ` >= 2000"
      },
      {
        "operator": "FILTER",
        "description": "Matches strings starting with argument",
        "example": "`Name` FILTER \"Me\""
      },
      {
        "operator": "CONTAINS",
        "description": "Searches whole strings for matches",
        "example": "`Name` CONTAINS \"an\""
      },
      {
        "operator": "IS EMPTY",
        "description": "Matches empty values",
        "example": "`E-Mail` IS EMPTY"
      },
      {
        "operator": "IN",
        "description": "Matches multiple values",
        "example": "`Status` IN (\"Aktiv\", \"Passiv\")"
      },
      {
        "operator": "WITH",
        "description": "Links queries of multiple properties in linked objects",
        "example": "WITH $links.debitor (totalamount > 100 AND remainingamount > 0)"
      }
    ],
    "specialProperties": [
      {
        "property": "$parents.<property>",
        "description": "Query a property of a parent"
      },
      {
        "property": "$ancestors.<property>",
        "description": "Query a property of any ancestor"
      },
      {
        "property": "$children.<childtype>.<property>",
        "description": "Query a property of a child"
      },
      {
        "property": "$links.<category>.<property>",
        "description": "Query a property of a link"
      },
      {
        "property": "$readonly",
        "description": "Boolean indicating if object is readonly"
      },
      {
        "property": "$writable",
        "description": "Boolean indicating if object is writable"
      },
      {
        "property": "$label",
        "description": "Label of the object"
      },
      {
        "property": "$id",
        "description": "ID of the object"
      }
    ],
    "functions": [
      {
        "function": "LOWER(<string>)",
        "description": "Converts text to lowercase"
      },
      {
        "function": "UPPER(<string>)",
        "description": "Converts text to uppercase"
      },
      {
        "function": "TRIM(<string>)",
        "description": "Trims whitespace"
      },
      {
        "function": "DAY(<date>)",
        "description": "Returns day of date"
      },
      {
        "function": "MONTH(<date>)",
        "description": "Returns month of date"
      },
      {
        "function": "YEAR(<date>)",
        "description": "Returns year of date"
      },
      {
        "function": "AGE(<date>)",
        "description": "Returns age from date"
      },
      {
        "function": "TODAY()",
        "description": "Returns current date"
      }
    ]
  },
  "responseCodes": {
    "200": "OK: Request successful",
    "201": "Created: Resource has been created",
    "204": "No Content: Request successful, no content returned",
    "304": "Not Modified: Content has not changed",
    "400": "Bad Request: Invalid parameter passed",
    "401": "Unauthorized: Authentication failed",
    "403": "Forbidden: No permission to perform request",
    "404": "Not Found: Resource was not found",
    "413": "Request Entity Too Large: Try splitting request",
    "425": "Quota Exceeded: Webling subscription limit reached",
    "429": "Too Many Requests: Rate limit exceeded",
    "500": "Server Error: Internal server error occurred",
    "501": "Not Implemented: Call not yet implemented",
    "503": "Service Unavailable: Server cannot handle request"
  },
  "rateLimit": {
    "requests": 500,
    "period": "per minute",
    "description": "API enforces rate limit of 500 requests per minute. Applications should not send more than 50 requests per minute."
  },
  "pagination": {
    "description": "Limit number of results with pagination parameters",
    "parameters": [
      {
        "name": "page",
        "type": "number",
        "description": "Page number, starting with 1",
        "example": "page=1"
      },
      {
        "name": "per_page",
        "type": "number",
        "description": "Number of results per page",
        "example": "per_page=100"
      }
    ]
  },
  "examples": {
    "php": {
      "description": "PHP example to get member title",
      "code": "<?php\n$apiurl = 'https://demo.webling.ch';\n$apikey = '<your_api_key>';\n\n$url = $apiurl . '/api/1/membergroup/1?apikey=' . $apikey;\n$curl = curl_init();\ncurl_setopt($curl, CURLOPT_URL, $url);\ncurl_setopt($curl, CURLOPT_RETURNTRANSFER, true);\n$data = json_decode(curl_exec($curl), true);\necho $data['properties']['title'];\n?>"
    },
    "javascript": {
      "description": "JavaScript example to get membergroup title",
      "code": "<!doctype html>\n<html>\n<head>\n    <script src=\"https://code.jquery.com/jquery-2.1.4.min.js\"></script>\n    <script>\n        var apiurl = 'https://demo.webling.ch';\n        var apikey = '<your_api_key>';\n        $(function(){\n            $.ajax(apiurl + '/api/1/membergroup/1?apikey=' + apikey).then(\n                function(data) {\n                    $('#title').html(data.properties.title);\n                }\n            );\n        })\n    </script>\n</head>\n<body>\n    <div id=\"title\"></div>\n</body>\n</html>"
    }
  }
}
```
