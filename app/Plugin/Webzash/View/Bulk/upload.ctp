<h2>Upload an OFX File</h2>

<p>
<ul>
	<li>Make sure you have a bank account setup with the last four numbers of your bank account number as the last four
		digits of the account/ledger name.
	</li>
	<li> Maks sure you have an 'UNALLOCATED' account setup as well. Place it in Equity. And check off the reconciliation
		box.
	</li>
	<li>TODO: validation for the above things and/or autocreation</li>
</ul>
</p>

<?php echo $this->Form->create('Bulk', array('type' => 'file')); ?>

<div class="form-group">
	<?php echo $this->Form->input('file', array('type' => 'file', 'label' => 'OFX File')); ?>
</div>

<div class="form-group">
	<?php echo $this->Form->button('Upload'); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php if (!empty($transactions)): ?>
	<h3>Transactions:</h3>

	<ul>
		<?php foreach ($transactions as $transaction): ?>
			<li>
				<?php echo h($transaction->date->format('Y-m-d')); ?>:
				<?php echo h($transaction->amount); ?> -
				<?php echo h($transaction->memo); ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
