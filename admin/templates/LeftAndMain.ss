<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-language" content="$i18nLocale" />
<% base_tag %>
<title>$ApplicationName | $SectionTitle</title>
</head>

<body class="loading cms $CSSClasses">
	
	<% include CMSLoadingScreen %>
	
	<div class="cms-container center" data-layout="{type: 'border'}">
	
		<div class="cms-preview-header north">
			Caution! The CMS is in alpha stage, and might not behave as expected. Get updates on the 
			<a href="https://groups.google.com/forum/#!forum/silverstripe-dev">developer mailinglist</a>
			and help us by
			<a href="http://silverstripe.org/contribute">contributing</a> and
			<a href="http://open.silverstripe.org">reporting bugs</a>.
		</div>

		$Menu

		$Content
		
		<% if currentPage %>
		<div class="cms-preview east">
			<iframe src="$currentPage.Link/?stage=Stage"></iframe>
		</div>
		<% end_if %>

	</div>
		
	<div id="cms-editor-dialogs">
		<% control EditorToolbar %>
			$ImageForm
			$LinkForm
			$FlashForm
		<% end_control %>
	</div>

	<!-- <div class="ss-cms-bottom-bar">
			<div class="holder">
				<div id="switchView" class="bottomTabs">
					<% if ShowSwitchView %>
						<div class="blank"> <% _t('VIEWPAGEIN','Page view:') %> </div>
						<span id="SwitchView">$SwitchView</span>
					<% end_if %>
				</div>
			</div>
		</div> -->

</body>
</html>
