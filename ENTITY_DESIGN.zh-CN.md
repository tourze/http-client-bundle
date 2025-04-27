# HttpClientBundle 数据库实体设计

本模块包含如下数据库实体：

## HttpRequestLog（请求外部接口日志）

- 表名：`http_request`
- 主要作用：记录所有通过 HTTP 客户端发起的外部接口请求日志，便于后续追踪、排查和统计分析。

### 字段说明

| 字段名            | 类型          | 说明                 |
|-------------------|---------------|----------------------|
| id                | int           | 主键，自增ID         |
| requestUrl        | string(512)   | 请求链接             |
| method            | string(20)    | 请求方式（GET/POST等）|
| content           | text          | 请求内容             |
| response          | text          | 响应内容             |
| exception         | text          | 异常信息             |
| stopwatchDuration | decimal(12,2) | 执行时长（毫秒）     |
| requestOptions    | array         | 原始请求参数         |
| createTime        | datetime      | 创建时间             |
| createdBy         | string        | 创建人               |
| createdFromIp     | string(45)    | 创建时IP             |
| userAgent         | string        | 创建时User-Agent     |

### 设计说明

- 该实体用于记录每一次外部接口请求的详细信息，包括请求与响应内容、异常、耗时、发起人、来源IP等。
- 支持定时清理（通过 `AsScheduleClean` 注解），可配置保留天数，避免日志表无限增长。
- 通过多种注解支持 EasyAdmin 后台管理、权限控制、导出、索引等功能，方便后台查看和管理。
- 可与用户体系集成，追踪每条日志的发起人。

如需扩展日志内容，可在实体类中新增字段并同步更新数据库结构。
