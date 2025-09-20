zbp.plugin.unbind("comment.reply.start", "system")
zbp.plugin.on("comment.reply.start", "zbget", function(id) {
  var i = id
  $("#inpRevID").val(i)
  var frm = $('#comment')
  var cancel = $("#cancel-reply")

  frm.before($("<div id='temp-frm' style='display:none'>")).addClass("reply-frm")
  $('#AjaxComment' + i).before(frm)

  cancel.show().click(function() {
    var temp = $('#temp-frm')
    $("#inpRevID").val(0)
    if (!temp.length || !frm.length) return
    temp.before(frm)
    temp.remove()
    $(this).hide()
    frm.removeClass("reply-frm")
    return false
  })
  try {
    $('#txaArticle').focus()
  } catch (e) {

  }
  return false
})

zbp.plugin.on("comment.get", "zbget", function (logid, page) {
  $('span.commentspage').html("Waiting...")
})

zbp.plugin.on("comment.got", "zbget", function () {
  $("#cancel-reply").click()
})

zbp.plugin.on("comment.post.success", "zbget", function () {
  $("#cancel-reply").click()
})