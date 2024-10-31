const noticeStatusStyles = {
    error: {
        color: '#D05D64',
        background: '#F9DDE0',
        borderColor: '#F7CED3',
    }
}

const noticeStyles = status => ({
    ...noticeStatusStyles[status],
    borderWidth: '2px',
    borderStyle: 'solid',
    borderRadius: '3px',
    padding: '5px 10px',
    fontSize: '14px',
    lineHeight: '20px',
    margin: '5px 0',
})

export default ({ status, children }) => (
    <div style={ noticeStyles(status) }>
        { children }
    </div>
)
